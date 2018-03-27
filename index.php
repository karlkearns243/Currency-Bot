<!-- Start of Bot: 9/25/2017 	Balance: 48,923.00 -->
<html>
	<head>
		<title>Forex Follow Trend</title>
		<title>Test</title>
		<link rel="stylesheet" type="text/css" href="CSS/Forex.css">
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.1/jquery-ui.min.js"></script>
		<script src="JS/Functions.js"></script>
		<script>
			checkData();
			UpdateTradeTable();
			setInterval(function(){checkData()}, 60000);
			setInterval(function(){UpdateTradeTable()}, 5000);
		</script>
	</head>
	<body>
		<div id="start-interface">Realized Profit: #N/A | Unrealized Profit: #N/A | Margin Balance: #N/A | Start: #N/A</div>
		<br>
		<div id="results">
			<table id="results-table">
				<thead>
					<tr>
						<th>Market</th>
						<th>Unrealized PIP</th>
						<th>Unrealized PIP <br/>Take Profit</th>
						<th>Unrealized PIP <br/>Take Loss</th>
						<th>Unrealized PIP <br/>Reverse</th>
						<th>Rate</th>
						<th>Amount</th>
						<th>Current Rate</th>
					</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</body>
</html>