function checkData(){
	//get follow trend logic
	$.ajax({
		type : 'POST',
		url : 'FollowTrendLogic.php',
		success: function(data){
			var TotalUnrealizedPIP = 0;
			var data = $.parseJSON("[" + data + "]");
			$.each(data, function(key, val){
				if(val['Market'] != undefined){
					var Market = val['Market'];
					var UnrealizedPIP = val['UnrealizedPIP'];
					if(UnrealizedPIP > 0) style = "background-color: #b3f783;"; else style = "background-color: #f783a3;";
					TotalUnrealizedPIP += UnrealizedPIP;
					var UnrealizedPIPLimit = val['UnrealizedPIPLimit'];
					var UnrealizedPIPStopLimit = val['UnrealizedPIPStopLimit'];
					var UnrealizedPIPSoftStopLimit = val['UnrealizedPIPSoftStopLimit'];
					var Rate = val['Rate'];
					var Amount = val['Amount'];
					var CurrentRate = val['Current Rate'];
					$('#results-table tbody').append('<tr class="child"><td>' + Market + '</td><td style = "' + style + '">' + UnrealizedPIP.toFixed(2) + '</td><td>' + UnrealizedPIPLimit + '</td><td>' + UnrealizedPIPStopLimit + '</td><td>' + UnrealizedPIPSoftStopLimit + '</td><td>' + Rate + '</td><td>' + Amount + '</td><td>' + CurrentRate + '</td></tr>');
				}
			});
			if(TotalUnrealizedPIP > 0) style = "background-color: #b3f783;"; else style = "background-color: #f783a3;";
			$('#results-table tbody').append('<tr class="child total"><td>Total</td><td style = "' + style + '">' + TotalUnrealizedPIP.toFixed(2) + '</td><td></td><td></td><td></td><td></td><td></td><td></td></tr>');
			$('#results-table tbody').append('<tr class="child blank"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>');
		},
		error: function(){
			$("#results").html("An error occured while attempting to perform follow trend logic.");
		}
	});
}

function UpdateTradeTable(){
	$.ajax({
		type : 'POST',
		url : 'UpdateTrades.php',
		success: function(data){
			var data = $.parseJSON(data);
			var RealizedProfit = data['RealizedProfit'];
			var UnrealizedProfit = data['UnrealizedProfit'];
			var MarginBalance = data['MarginBalance'];
			var Start = 2.00;
			$("#start-interface").html("Realized Profit: " + RealizedProfit + " | Unrealized Profit: " + UnrealizedProfit + " | Margin Balance: " + MarginBalance + " | Start: " + Start.toFixed(2));
		},
		error: function(){
			$("#results").html("An error occured while attempting to perform follow trend logic.");
		}
	});
}