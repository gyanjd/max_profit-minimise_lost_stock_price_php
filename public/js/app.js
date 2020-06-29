$(document).ready(function(){

	$(".alert-msg").hide();

	$(".input-daterange").datepicker({
		format: 'dd/mm/yyyy'
	});


	$("#csvUpload").submit(function(e) {
		e.preventDefault();

		var fd = new FormData();
		fd.append('file', $("#csv-file").prop('files')[0]);
		fd.append('fn', 'upload_csv');  
	    $.ajax({
	        url: "controller/Controller.php",
	        type: "post",
	        dataType: 'json',
	        mimeType: 'multipart/form-data',
	        processData: false, 
	        contentType: false,
	        data: fd,
	        success: function(data) {

	            let statusType;
	            let statusMsg;

			    switch(data){
			        case 'success':
			            statusType = 'alert-success';
			            statusMsg = 'File upload successfully.';
	    				$("#csvUpload")[0].reset();
			            break;
			        case 'error':
			            statusType = 'alert-danger';
			            statusMsg = 'Some problem occurred, please try again.';
			            break;
			        case 'invalid_file':
			            statusType = 'alert-danger';
			            statusMsg = 'Please upload a valid CSV file.';
			            break;
			        default:
			            statusType = '';
			            statusMsg = '';
			    }

	            $(".responseMsg").text(statusMsg);
	            $(".responseMsg").addClass(statusType);

	            $(".alert-msg").show();
	            setTimeout(function(){
	            	$(".alert-msg").hide(); 
	            	$(".responseMsg").removeClass(statusType);
	            	$(".responseMsg").text('');
	            }, 3000);

				fetchStockList();

	        },
	        error: function() {
	            statusType = 'alert-danger';
		        statusMsg = 'Some problem occurred, please try again.';

	            $(".alert-msg").show();
	            setTimeout(function(){
	            	$(".alert-msg").hide(); 
	            	$(".responseMsg").removeClass(statusType);
	            	$(".responseMsg").text('');
	            }, 3000);    
	        }
	    });
	});

	function fetchStockList(){
    	data = "fn=stock_list";
		$.ajax({
	        url: "controller/Controller.php",
	        type: "GET",
	        dataType: 'json',
	        processData: false, 
	        contentType: false,
	        data: data,
	        success: function(data) {
	        	var stock_list = data.items;
	        	$("#last_upload").text('Last CSV file upload : ' + stock_list[0].created_at);
	        	$.each(stock_list, function(key, value) {   
				     $('#stock_list').append($("<option></option>")
				        .attr("value", value.stock_name)
				        .text(value.stock_name)); 
	        		console.log(value.stock_name);
				});
	        },
	        error: function() {
	        	alert('error');
	        }
	    });
	}

	fetchStockList();

	function fetchStockDate(stock_name){
    	data = "fn=stock_date";
    	data += "&stock_name="+stock_name;
		$.ajax({
	        url: "controller/Controller.php",
	        type: "GET",
	        dataType: 'json',
	        processData: false, 
	        contentType: false,
	        data: data,
	        success: function(data) {
	        	let stock_date = data.items;
				$(".input-daterange").datepicker("remove");

			    $(".input-daterange").datepicker({
				    format: 'dd/mm/yyyy',
				    startDate: stock_date[0].min_date,
		    		endDate: stock_date[0].max_date,
				});
	        },
	        error: function() {
	        	alert('error');
	        }
	    });
	}

	$("#stock_list").change(function(){
		let stock_name = $("#stock_list option:selected").val();
		$("#start").val('');
		$("#end").val('');
		fetchStockDate(stock_name);
	})

	$("#trackForm").submit(function(e) {
		e.preventDefault();

		if($("#stock_list").val() == -1){
			alert('Please select stock name');
			return 0;
		}	

		if($("#start").val() == $("#end").val()){
			alert('Select Different Date');
			return 0;
		}

		data = $(this).serialize();
		data +='&fn=calculate_profit';  
	    $.ajax({
	        url: "controller/Controller.php",
	        type: "GET",
	        dataType: 'json',
	        processData: false, 
	        contentType: false,
	        data: data,
	        success: function(data) {

	        	if(data.error == 0){
	        		$(".result").show();
	        		$("#mean").text(data.mean);
		        	$("#deviation").text(data.deviation);

		        	if(data.profit < 0){
		        		$("#profit").text('Rs ' + data.profit).css("color", "red");
		        	} else {
		        		$("#profit").text('Rs ' + data.profit).css("color", "green");
		        	}

		        	$("#buy_date").text(data.buy_date);
		        	$("#sell_date").text(data.sell_date);
	        	} else {
					alert('Insufficient Data for given date range, please select different date range')		        		
	        	}
	        },
	        error: function() {
	        	alert('error');
	        }
	    });
	});
	
});
