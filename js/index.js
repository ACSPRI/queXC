Element.Events.keyenter = {
	base: 'keypress',
	condition: function(e){
		// We can basically put any logic here.
		// In this example we return true, when the pressed key is the
		// Enter-Button so the keyenter event gets fired.
		return e.key=='enter';
	}
};

function getSelectedText()
{
    var txt = '';
     if (window.getSelection)
    {
        txt = window.getSelection();
             }
    else if (document.getSelection)
    {
        txt = document.getSelection();
            }
    else if (document.selection)
    {
        txt = document.selection.createRange().text;
            }
    else return;
    e = document.getElementById('selectedText');
    e.value = txt;
}

function showhide(eid)
{
	e = document.getElementById(eid);
	input = e.getElementsByTagName('input');
	el = false;

	//Deselect all radio elements and get the first radio element in el
	for (var i  = 0; i < input.length; i++)
	{
		type = input[i].getAttribute('type')
		if (type == 'radio')
		{
			if (el == false)
				el = input[i];

			el.checked=false;
		}
	}

	if (e.style.display == 'none')
	{
		e.style.display = 'inline';
		el.checked = true;
	}
	else
	{
		e.style.display = 'none';
	}
}

function addevents() {
	$('searchclick').addEvent('click', function(event) {

		//make the ajax call, replace text
		var req = new Request.HTML({
			method: 'get',
			evalScripts: true,
			url: 'index.php',
			data: { 'search' : $('search').value },
			update: $('header'),
			}
		).send();

	});

	$('search').addEvent('keyenter', function(event) {
		//Stop page submit
		event.stop();

		//make the ajax call, replace text
		var req = new Request.HTML({
			method: 'get',
			evalScripts: true,
			url: 'index.php',
			data: { 'search' : $('search').value },
			update: $('header'),
			}
		).send();

	});


}

function updateevents() {
	$$('.rb').addEvent('click', function(event) {
		//prevent the page from changing
		event.stop();

		//make the ajax call, replace text
		var req = new Request.HTML({
			method: 'get',
			evalScripts: true,
			url: 'index.php',
			data: { 'display_codes' : this.id },
			update: $('header'),
			}
		).send();

	});


}

function updateevents2() {
	$$('.rb').addEvent('click', function(event) {
		//prevent the page from changing
		event.stop();

		//make the ajax call, replace text
		var req = new Request.HTML({
			method: 'get',
			evalScripts: true,
			url: 'displayscheme.php',
			data: { 'display_codes' : this.id },
			update: $('header'),
			}
		).send();

	});
}

//on dom ready...
window.addEvent('domready', function() {updateevents(); addevents();});
