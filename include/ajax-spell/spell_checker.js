var sc_spellCheckers = null;

window.addEvent('domready', function() {

    sc_spellCheckers = new SpellChecker(
                            'spell_check',
                            {
                                confirmAfterAllWordsChecked: false,
                                alertOnNoMisspellings: false,
                                controlsBelow: true
                            }
                        );

   //Automatically start spell checker on first box
   el = $$('.spell_check');
   sc_spellCheckers.current = el[0];
   sc_spellCheckers.spellCheck(el[0]);
});

var SpellChecker = new Class({

        options: {
            useImages: true,
            url: 'include/ajax-spell/spell_checker.php',
            confirmAfterAllWordsChecked: true,
            alertOnNoMisspellings: false,
            controlsBelow: false
        },

        textBoxes: null,
        current: null,
        suggestDiv: null,
        currentSuggestionWord: null,

        initialize: function(spellClass, options) {

                document.addEvent('click', function(e) {
				    var event = new Event(e);

                    if (!$(event.target).hasClass('suggestion')) {
                        if (this.suggestDiv != null) {
                            this.suggestDiv.dispose();
                            this.suggestDiv = null;

                            if (!$(event.target).hasClass('spell_checker_cp_check')) {
                                this.checkUncheckedWords();
                            }
                        }
                    }

				    return true;
				}.bind(this));

                this.textBoxes = $$('.' + spellClass);

                this.setOptions(options);

                this.textBoxes.each(function (el) {

                    //generates the div to hold the spell checker controls
				    var cpDiv = new Element('div');
				    cpDiv.addClass('spell_checker_cp');

				    // add the link to check the spelling
			        if (this.options.useImages) {

				        var checkLink = new Element('a');
				        checkLink.set('html', '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
			            checkLink.addClass('spell_checker_cp_check');
			            checkLink.alt = 'Check Spelling & Preview';

			        } else {

			            var checkLink = new Element('a');
			            checkLink.set('html', 'Check Spelling & Preview');

			        }

			        checkLink.title = 'Check Spelling & Preview';

			        checkLink.addEvent('click', function (){

			            this.current = el;
			            this.spellCheck(el);

			        }.bind(this));

				    cpDiv.adopt(checkLink);

				    //add the link to resume editing
				    var resumeLink = new Element('a');
				    if (this.options.useImages) {
				        resumeLink.set('html', '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
			            resumeLink.addClass('spell_checker_cp_resume');
			            resumeLink.alt = 'Resume Editing';
			        } else {
			            resumeLink.set('html', 'Resume Editing');
			        }

			        resumeLink.title = 'Resume Editng';

			        resumeLink.addEvent('click', function (){

			            this.current = el;
			            this.resume(el);

			        }.bind(this));

			        resumeLink.setStyle('display', 'none');

				    cpDiv.adopt(resumeLink);

				    //the span that lets the user know of the status of the spell checker
                    var statusSpan = new Element('span');
                    statusSpan.addClass('spell_checker_cp_status');
                    statusSpan.set('html', '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
                    cpDiv.adopt(statusSpan);

                    var workingSpan = new Element('span');
				    workingSpan.set('html', '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
			        workingSpan.addClass('spell_checker_cp_working');
			        workingSpan.alt = 'Working...';
			        workingSpan.setStyle('display', 'none');
			        cpDiv.adopt(workingSpan);

			        if (this.options.controlsBelow) {
				        cpDiv.injectAfter(el);
			        } else {
			            cpDiv.injectBefore(el);
			        }

                }, this);
        },

        spellCheck: function(el) {

            var cpDiv = null;
            var resultDiv = null;

            if (this.options.controlsBelow) {
                cpDiv = el.getNext();
                resultDiv = el.getPrevious();
            } else {
                cpDiv = el.getPrevious();
                resultDiv = el.getNext();
            }

            cpDiv.getLast().getPrevious().set('text', ''); // status span
            cpDiv.getLast().setStyles({'display': ''}); // progress indicator

            var varStr = Hash.toQueryString({
                                    spellText: el.value,
                                    action: 'spellcheck'
                         });
            var checkUrl = this.options.url;
            var ajaxCall = new Request(
                               {
                                   url: checkUrl,
                                   method: 'post',
                                   data: varStr,
                                   onSuccess: this.spellCheck_cb.bind(this)
                               }
                           ).send();
        },

        spellCheck_cb: function(resultText) {

            var cpDiv = null;
            var resultDiv = null;

            if (this.options.controlsBelow) {
                cpDiv = this.current.getNext();
            } else {
                cpDiv = this.current.getPrevious();
            }

            if (resultText == 0) {

                if (this.options.alertOnNoMisspellings) {
                    alert('No misspellings found');
                }

                cpDiv.getLast().getPrevious().set('text', 'No misspellings found'); // status span

            } else {

                var checkAction = cpDiv.getFirst();
                checkAction.setStyles({'display': 'none'});
                var resumeAction = checkAction.getNext();
                resumeAction.setStyles({'display': ''});

                var resultDiv = new Element('div');
                var elSize = this.current.getSize();
                resultDiv.setStyle('width', elSize.x);
                resultDiv.setStyle('height', elSize.y);
                resultDiv.setStyle('display', 'none');

                this.current.setStyle('display', 'none');

                resultDiv.setStyle('display', '');
                resultDiv.addClass('spell_checker_cp_result');
                resultDiv.set('html', resultText);

                resultDiv.getElements('span').each(function(el) {

                    // add this class to mark it as misspelled
                    el.addClass('sc_misspelled');

                    // add this class to show it's not been looked at yet
                    el.addClass('sc_unchecked');

                    el.addEvent('click', function(e) {

                        this.currentSuggestionWord = el;

                        if (this.options.controlsBelow) {
                            this.current = resultDiv.getNext();
                        } else {
                            this.current = resultDiv.getPrevious();
                        }

                        // we remove this class so we know it's been clicked on
                        el.removeClass('sc_unchecked');

                        var event = new Event(e);
                        event.stop();

                        var varStr = Hash.toQueryString({
                                                suggestionText: el.get('text'),
                                                action: 'suggest'
                                     });
                        var checkUrl = this.options.url;
                        var ajaxCall = new Request(
                                   {
                                       url: checkUrl,
                                       method: 'post',
                                       data: varStr,
                                       onSuccess: this.suggest_cb.bind(this)
                                   }
                               ).send();

                    }.bind(this));
                }, this);

                // we have to inject it here because IE has no idea how to handle
                // it if it's injected before stuff is done to it (like inserting HTML)
                if (this.options.controlsBelow) {
                    resultDiv.injectBefore(this.current);
                } else {
                    resultDiv.injectAfter(this.current);
                }
            }

            cpDiv.getLast().setStyles({'display': 'none'}); //hide spinner
        },

        suggest_cb: function(txt) {

            if (this.suggestDiv != null) {
                this.suggestDiv.dispose();
                this.suggestDiv = null;
            }

            this.suggestDiv = new Element('div');
            this.suggestDiv.setStyles({'display': 'none'});
            this.suggestDiv.addClass('suggestionBox');
            this.suggestDiv.set('html', txt);

            this.suggestDiv.injectAfter(this.currentSuggestionWord);

            this.suggestDiv.getElements('div').each(function(el){

                if (el.hasClass('suggestion')) {

                    el.addEvent('click', function(e) {
                        var event = new Event(e);
                        event.stop();

                        this.currentSuggestionWord.set('text', el.get('text'));
                        this.currentSuggestionWord.addClass('corrected');

                        this.suggestDiv.dispose();
                        this.suggestDiv = null;

                        this.checkUncheckedWords();

                    }.bind(this));

                } else if (el.hasClass('addToDictionary')) {

                    el.addEvent('click', function(e) {
                        var event = new Event(e);
                        event.stop();

                        this.currentSuggestionWord.addClass('corrected');

                        var varStr = Hash.toQueryString({
                                                            wordToAdd: this.currentSuggestionWord.get('text'),
                                                            action: 'addToDictionary'
                                                          });
                        var checkUrl = this.options.url;

                        var ajaxCall = new Request(
                                   {
                                       url: checkUrl,
                                       method: 'post',
                                       data: varStr,
                                       onSuccess: this.addToDictionary_cb.bind(this)
                                   }
                               ).send();

                        this.suggestDiv.dispose();
                        this.suggestDiv = null;

                        this.checkUncheckedWords();

                    }.bind(this));
                }

            }, this);

            this.suggestDiv.setPosition({
			    relativeTo: this.currentSuggestionWord,
			    position: 'bottomLeft'
			});

            this.suggestDiv.setStyles({'display': ''});
        },

        checkUncheckedWords: function() {

            var resultDiv = null;

            if (this.options.controlsBelow) {
                resultDiv = this.current.getPrevious();
            } else {
                resultDiv = this.current.getNext();
            }

            // check the result div to see if all the words have been touched
            var done = true;
            resultDiv.getElements('span').each(function(el) {
                if (el.hasClass('sc_unchecked')) {
                    done = false;
                }
            }.bind(this));

            if (done == true) {
                if (this.options.confirmAfterAllWordsChecked) {
                    if (confirm("All the misspelled words have been looked at, would you like to return to edit mode?")) {
                        this.resume(this.current);
                    }
                } else {
                    this.resume(this.current);
                }
            }
        },

        addToDictionary_cb: function(txt, xml) {

            if (txt == 1) {
                alert("Successfully added word to dictionary.");
            } else {
                alert("Failed adding word to dictionary.");
            }

        },

        resume: function(el) {

            if (this.suggestDiv != null) {
                this.suggestDiv.dispose();
                this.suggestDiv = null;
            }

            var resultDiv = null;
            var cpDiv = null;

            if (this.options.controlsBelow) {
                resultDiv = el.getPrevious();
                cpDiv = el.getNext();
            } else {
                resultDiv = el.getNext();
                cpDiv = el.getPrevious();
            }

            resultDiv.getElements('span').each(function(el) {
                if (el.hasClass('sc_misspelled')) {
                    var tmp = document.newTextNode(el.get('text'));
            	    el.parentNode.replaceChild(tmp, el);
                }
            }.bind(this));

            el.value = resultDiv.get('html').replace(/<br>/gi, "\n");
            resultDiv.setStyles({'display': 'none'});
            el.setStyles({'display': ''});

            var checkAction = cpDiv.getFirst();
            checkAction.setStyles({'display': ''});
            var resumeAction = checkAction.getNext();
            resumeAction.setStyles({'display': 'none'});
        },

        resumeAll: function() {
             this.textBoxes.each(function (el) {
                 if (el.getStyle('display') == 'none') {
                    this.resume(el);
                 }
             }.bind(this));
        }
});

SpellChecker.implement(new Options, new Events);


/*MooTools, My Object Oriented Javascript Tools. Copyright (c) 2006-2007 Valerio Proietti, <http://mad4milk.net>, MIT Style License.||CNET Libraries Copyright (c) 2006-2008, http://clientside.cnet.com/wiki/cnet-libraries#license*/

Element.implement({

	expose: function(){
		if (this.getStyle('display') != 'none') return $empty;
		var before = {};
		['visibility', 'display', 'position'].each(function(style){
			before[style] = this.style[style]||'';
		}, this);
		this.setStyles({
			visibility: 'hidden',
			display: 'block',
			position:'absolute'
		});
		return (function(){
			this.setStyles(before);
		}).bind(this);
	},

	getDimensions: function(options) {
		options = $merge({computeSize: false},options);
		var dim = {};
		function getSize(el, options){
			if(options.computeSize) dim = el.getComputedSize(options);
			else {
				var sz = el.getSize();
				dim.width = sz.x;
				dim.height = sz.y;
			}
			return dim;
		}
		try {
			dim = getSize(this, options);
		}catch(e){}
		if(this.getStyle('display') == 'none'){
			var restore = this.expose();
			dim = getSize(this, options);
			restore();
		}
		return $merge(dim, {x: dim.width, y: dim.height});
	},

	getComputedSize: function(options){
		options = $merge({
			styles: ['padding','border'],
			plains: {height: ['top','bottom'], width: ['left','right']},
			mode: 'both'
		}, options);
		var size = {width: 0,height: 0};
		switch (options.mode){
			case 'vertical':
				delete size.width;
				delete options.plains.width;
				break;
			case 'horizontal':
				delete size.height;
				delete options.plains.height;
				break;
		}
		var getStyles = [];
		$each(options.plains, function(plain, key){
			plain.each(function(edge){
				options.styles.each(function(style){
					getStyles.push((style=="border")?style+'-'+edge+'-'+'width':style+'-'+edge);
				});
			});
		});
		var styles = this.getStyles.apply(this, getStyles);
		var subtracted = [];
		$each(options.plains, function(plain, key){
			size['total'+key.capitalize()] = 0;
			size['computed'+key.capitalize()] = 0;
			plain.each(function(edge){
				size['computed'+edge.capitalize()] = 0;
				getStyles.each(function(style,i){
					if(style.test(edge)) {
						styles[style] = styles[style].toInt();
						if(isNaN(styles[style]))styles[style]=0;
						size['total'+key.capitalize()] = size['total'+key.capitalize()]+styles[style];
						size['computed'+edge.capitalize()] = size['computed'+edge.capitalize()]+styles[style];
					}
					if(style.test(edge) && key!=style &&
						(style.test('border') || style.test('padding')) && !subtracted.contains(style)) {
						subtracted.push(style);
						size['computed'+key.capitalize()] = size['computed'+key.capitalize()]-styles[style];
					}
				});
			});
		});
		if($chk(size.width)) {
			size.width = size.width+this.offsetWidth+size.computedWidth;
			size.totalWidth = size.width + size.totalWidth;
			delete size.computedWidth;
		}
		if($chk(size.height)) {
			size.height = size.height+this.offsetHeight+size.computedHeight;
			size.totalHeight = size.height + size.totalHeight;
			delete size.computedHeight;
		}
		return $merge(styles, size);
	}
});

Element.implement({
	setPosition: function(options){
		options = $merge({
			relativeTo: document.body,
			position: {
				x: 'center',
				y: 'center'
			},
			edge: false,
			offset: {x:0,y:0},
			returnPos: false,
			relFixedPosition: false,
			ignoreMargins: false
		}, options);
		var parentOffset = {x: 0, y: 0};
		var parentPositioned = false;
		if(this.getParent() != document.body) {
			var parent = this.getParent();
			while(parent != document.body && parent.getStyle('position') == "static") {
				parent = parent.getParent();
			}
			if(parent != document.body) {
				parentOffset = parent.getPosition();
				parentPositioned = true;
			}
			options.offset.x = options.offset.x - parentOffset.x;
			options.offset.y = options.offset.y - parentOffset.y;
		}
		function fixValue(option) {
			if($type(option) != "string") return option;
			option = option.toLowerCase();
			var val = {};
			if(option.test('left')) val.x = 'left';
			else if(option.test('right')) val.x = 'right';
			else val.x = 'center';

			if(option.test('upper')||option.test('top')) val.y = 'top';
			else if (option.test('bottom')) val.y = 'bottom';
			else val.y = 'center';
			return val;
		};
		options.edge = fixValue(options.edge);
		options.position = fixValue(options.position);
		if(!options.edge) {
			if(options.position.x == 'center' && options.position.y == 'center') options.edge = {x:'center',y:'center'};
			else options.edge = {x:'left',y:'top'};
		}

		this.setStyle('position', 'absolute');
		var rel = $(options.relativeTo) || document.body;
    var top = (rel == document.body)?window.getScroll().y():rel.getTop();
    var left = (rel == document.body)?window.getScroll().x():rel.getLeft();

		if (top < 0) top = 0;
    if (left < 0) left = 0;
		var dim = this.getDimensions({computeSize: true, styles:['padding', 'border','margin']});
		if (options.ignoreMargins) {
			options.offset.x += ((options.edge && options.edge.x == "right")?dim['margin-right']:-dim['margin-left']);
			options.offset.y += ((options.edge && options.edge.y == "bottom")?dim['margin-bottom']:-dim['margin-top']);
		}
		var pos = {};
		var prefY = options.offset.y.toInt();
		var prefX = options.offset.x.toInt();
		switch(options.position.x) {
			case 'left':
				pos.x = left + prefX;
				break;
			case 'right':
				pos.x = left + prefX + rel.offsetWidth;
				break;
			default:
				pos.x = left + (((rel == document.body)?window.getSize().x:rel.offsetWidth)/2) + prefX;
				break;
		};
		switch(options.position.y) {
			case 'top':
				pos.y = top + prefY;
				break;
			case 'bottom':
				pos.y = top + prefY + rel.offsetHeight;
				break;
			default:
				pos.y = top + (((rel == document.body)?window.getSize().y:rel.offsetHeight)/2) + prefY;
				break;
		};

		if(options.edge){
			var edgeOffset = {};

			switch(options.edge.x) {
				case 'left':
					edgeOffset.x = 0;
					break;
				case 'right':
					edgeOffset.x = -dim.x-dim.computedRight-dim.computedLeft;
					break;
				default:
					edgeOffset.x = -(dim.x/2);
					break;
			};
			switch(options.edge.y) {
				case 'top':
					edgeOffset.y = 0;
					break;
				case 'bottom':
					edgeOffset.y = -dim.y-dim.computedTop-dim.computedBottom;
					break;
				default:
					edgeOffset.y = -(dim.y/2);
					break;
			};
			pos.x = pos.x+edgeOffset.x;
			pos.y = pos.y+edgeOffset.y;
		}
		pos = {
			left: ((pos.x >= 0 || parentPositioned)?pos.x:0).toInt(),
			top: ((pos.y >= 0 || parentPositioned)?pos.y:0).toInt()
		};
		if(rel.getStyle('position') == "fixed"||options.relFixedPosition) {
			pos.top = pos.top.toInt() + window.getScroll().y;
			pos.left = pos.left.toInt() + window.getScroll().x;
		}

		if(options.returnPos) return pos;
		else this.setStyles(pos);
		return this;
	}
});
