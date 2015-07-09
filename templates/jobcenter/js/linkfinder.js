$(document).ready(function() {

	var pathname = window.location.pathname;

	// make sure we are in the right controller
	if (pathname.indexOf("/jobcenter/linkfinder") > -1) {
		tailScroll();
		$("#issnfinder-button").click(function(e) {

			// reset all the counters
			$("#tail").text('');
			$('#issnfinder-new-count').text('0');
			$('#issnfinder-button-span').text('0');
			$("#issnfinder-button").css("display", "none");
			$("#issnfinder-button-stop").css("display", "inline-block");

			var nrOfIssn = listOfIssn.length;

			console.log(nrOfIssn);

			NProgress.configure({
				trickle: false
			});
			NProgress.start();

			// Inside the ajaxq call here, deal with the success and errors states for each call. 
			var ajax_caller = function(data) {
				return $.ajaxq("IssnQueue", {
					url: data.url,
					data: data.data,
					success: data.success,
					complete: data.complete
				});
			}

			// Create an array of deferred objects
			var ajax_calls = [];
			for (var i = 0; i < nrOfIssn; i++)
				ajax_calls.push(ajax_caller({
					url: urlBase + 'datagathering/import/',
					data: {
						uri: listOfIssn[i]["issn"],
						wrapper: "linkeddata"
					},
					success: function(message) {
						console.log(this);

						// since we can't access the listOfIssn in the success callback 
						// we need to use the url to get the issn
						var issn = decodeURIComponent(this.url.match(/uri=([^&]+)/)[1]);
						var now = new Date($.now());
						$("<div />").html(now + " - " + buildIssnUri(issn) + " - " + message.message).appendTo("#tail");
						if (message.message.indexOf("Statements were added") > -1) {
							$('#issnfinder-new-count').text(parseInt($('#issnfinder-new-count').text(), 10) + 1)
						}
					},
					complete: function(message) {
						NProgress.inc(1 / nrOfIssn); // inc progressbar
						tailScroll();
						$('#issnfinder-button-span').text(parseInt($('#issnfinder-button-span').text(), 10) + 1)
					}
				}));

			// $.when takes a comma separated list of deferred objects.
			// Apply unpacks array into a suitable list for $.when to handle.
			$.when.apply(this, ajax_calls).done(function() {
				$.ajax({
					url: 'deletemessagestack'
				});
				$("#issnfinder-button-stop").css("display", "none");
				$("#issnfinder-button").css("display", "inline-block");
			});
		});


		$("#issnfinder-button-stop").click(function(e) {
			$.ajax({
				url: 'deletemessagestack'
			});
			$("#issnfinder-button-stop").css("display", "none");
			$("#issnfinder-button").css("display", "inline-block");
			$.ajaxq.abort("IssnQueue");

		});
	}
});

// tail effect
function tailScroll() {
	var height = $("#tail").get(0).scrollHeight;
	$("#tail").animate({
		scrollTop: height
	}, 500);
}

function buildIssnUri (issn) {
	var url = urlBase + "resource/properties?=" + encodeURIComponent(issn);
	return "<a href=\"" + url + "\">" + issn + "</a>";
}