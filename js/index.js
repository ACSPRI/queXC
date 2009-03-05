function updateevents() {
	$$('.rb').addEvent('click', function(event) {
		//prevent the page from changing
		event.stop();

		//make the ajax call, replace text
		var req = new Request.HTML({
			method: 'get',
			evalScripts: true,
			url: 'index.php',
			data: { 'display_codes' : this.id.substr(1) },
			update: $('header'),
			}
		).send();

	});
}

//on dom ready...
window.addEvent('domready', function() {updateevents();});
