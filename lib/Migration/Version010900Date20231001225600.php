<?php

declare(strict_types=1);

namespace OCA\Music\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Migrate the DB schema to Music v1.9.0 level from the v1.4.0 level
 */
class Version010900Date20231001225600 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$this->fixInconsistentIdTypes($schema);
		$this->allowNegativeYear($schema);
		$this->ampacheSessionChanges($schema);
		$this->addRatingFields($schema);
		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}

	/**
	 * Some of the foreign keys referring to entity IDs have been previously defined as signed
	 * although the referred primary key has always been unsigned.
	 */
	private function fixInconsistentIdTypes(ISchemaWrapper $schema) {
		$schema->getTable('music_albums')->changeColumn('album_artist_id', ['unsigned' => true]);
		$schema->getTable('music_tracks')->changeColumn('artist_id', ['unsigned' => true])
										->changeColumn('album_id', ['unsigned' => true]);
		$schema->getTable('music_bookmarks')->changeColumn('entry_id', ['unsigned' => true]);
		$schema->getTable('music_ampache_users')->changeColumn('id', ['unsigned' => true]);
	}

	/**
	 * Although untypical, it's not totally impossible that some historical piece of music would
	 * be tagged with a negative year indicating a year BCE.
	 */
	private function allowNegativeYear(ISchemaWrapper $schema) {
		$schema->getTable('music_tracks')->changeColumn('year', ['unsigned' => false]);
	}

	/**
	 * Add the new fields to the `music_ampache_sessions` table
	 */
	private function ampacheSessionChanges(ISchemaWrapper $schema) {
		// On SQLite, it's not possible to add notnull columns to an existing table without a default value, see
		// https://stackoverflow.com/questions/3170634/cannot-add-a-not-null-column-with-default-value-null-in-sqlite3.
		// To work around this, we need to recreate the entire music_ampache_sessions table from scratch.
		$schema->dropTable('music_ampache_sessions');
		$table = $schema->createTable('music_ampache_sessions');

		$table->addColumn('id',					'integer',	['autoincrement' => true, 'notnull' => true, 'unsigned' => true]);
		$table->addColumn('user_id',			'string',	['notnull' => true, 'length' => 64]);
		$table->addColumn('token',				'string',	['notnull' => true, 'length' => 64]);
		$table->addColumn('expiry',				'integer',	['notnull' => true, 'unsigned' => true]);
		$table->addColumn('api_version',		'string',	['notnull' => false, 'length' => 16]);
		$table->addColumn('ampache_user_id',	'integer',	['notnull' => true, 'unsigned' => true]);

		$table->setPrimaryKey(['id']);
		$table->addUniqueIndex(['token'], 'music_ampache_sessions_index');
	}

	/**
	 * Add the new field 'rating' to applicable tables
	 */
	private function addRatingFields(ISchemaWrapper $schema) {
		$tableNames = [
			'music_artists',
			'music_albums',
			'music_tracks',
			'music_playlists',
			'music_podcast_channels',
			'music_podcast_episodes'
		];

		foreach ($tableNames as $tableName) {
			$table = $schema->getTable($tableName);
			$this->setColumn($table, 'rating', 'integer', ['notnull' => true, 'default' => 0]);
		}

		// Also, add 'starred' field for playlists
		$this->setColumn($schema->getTable('music_playlists'), 'starred', 'datetime', ['notnull' => false]);
	}

	private function setColumn($table, string $name, string $type, array $args) {
		if (!$table->hasColumn($name)) {
			$table->addColumn($name, $type, $args);
		}
	}
}