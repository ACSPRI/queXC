function LinkUp(element) 
{
	var number = document.getElementById(element).selectedIndex;
	location.href = document.getElementById(element).options[number].value;
}
