Element.implement({
	getSelection: function() {
		var text = '', length = 0, start = 0, end = 0, doc = this.getDocument();
		
		if ($defined(window.getSelection)) {
			var selection = window.getSelection();
			
			text = selection.toString();
			length = text.length;
			start = selection.anchorOffset - length;
			end = selection.anchorOffset;
			
			if (Browser.Engine.presto) {
				start = selection.anchorOffset;
				end = start + length;
			}
		}
		else if ($defined(doc.selection)) {
			var range = doc.selection.createRange(),
				duplicate = range.duplicate(),
				value = this.get('html'),
				offset = value.length - value.match(/[\n\r]*$/)[0].length;
			
			duplicate.setEndPoint('StartToEnd', range);
			end = offset - duplicate.text.length;		
			duplicate.setEndPoint('StartToStart', range);
			start = offset - duplicate.text.length;	
			
			text = duplicate.text;
			length = text.length;
		}
		
		return {
			'text': text,
			'length': length,
			'start': start,
			'end': end
		}
	}
});

