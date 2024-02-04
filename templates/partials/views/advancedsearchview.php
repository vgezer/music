<div class="view-container " id="advanced-search-area">
	<h1 translate>Advanced search</h1>

	<table id="adv-search-rules" class="grid">
		<tr class="adv-search-rule-row" ng-repeat="rule in searchRules">
			<td><select ng-model="rule.rule"><option ng-repeat="ruleType in searchRuleTypes" value="{{ ruleType.key }}">{{ ruleType.name }}</option></select></td>
			<td><select ng-model="rule.operator"><option ng-repeat="ruleOp in searchRuleOperators" value="{{ ruleOp.key }}">{{ ruleOp.name }}</option></select></td>
			<td><input type="text" ng-model="rule.input"/></td>
			<td><a class="icon icon-close" ng-click="removeSearchRule($index)"></a></td>
		</tr>
		<tr class="add-row clickable" ng-click="addSearchRule()">
			<td><a class="icon icon-add"></a></td>
			<td></td>
			<td></td>
			<td></td>
		</tr>
	</table>
	<button ng-click="search()" translate>Search</button>

	<div class="playlist-area" ng-if="resultTracks">
        <h2 class="clickable" ng-click="onHeaderClick()"
            translate translate-n="resultTracks.length" translate-plural="{{ resultTracks.length }} results">
            1 result
        </h2>
		<track-list
			tracks="resultTracks"
			get-track-data="getTrackData"
			play-track="onTrackClick"
			show-track-details="showTrackDetails"
			get-draggable="getDraggable"
		>
		</track-list>
	</div>
</div>
