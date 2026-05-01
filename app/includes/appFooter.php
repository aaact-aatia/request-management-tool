<!-- Write closure template -->
		<script>
			var defFooter = document.getElementById("def-footer");
			defFooter.outerHTML = wet.builder.appFooter({
				"showFeatures": false
			});
		</script>
		<!-- Write closure template -->
		<script>
			document.write(wet.builder.refFooter({
				"isApplication": true
			}));
		</script>