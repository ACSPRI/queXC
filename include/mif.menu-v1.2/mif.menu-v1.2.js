/*
---
 
name: Mif.Core
description: define Mif object, utility
license: MIT-Style License (http://mifjs.net/license.txt)
copyright: Anton Samoylov (http://mifjs.net)
authors: Anton Samoylov (http://mifjs.net)
requires: 
  - core:1.2.4:*
provides: Mif.Core
 
...
*/

var Mif = {};

Mif.ids={};
Mif.id=function(id){
	return Mif.ids[id];
}
Mif.uids={};
Mif.UID=0;

Element.implement({

	getAncestor: function(match, top){//includes self
		var parent=this;
		while(parent){
			if(parent.match(match)) return parent;
			parent=parent.getParent();
			if(parent==top) return false;
		}
		return false;
	}
	
});

Array.implement({
	
	inject: function(added, current, where){//inject added after or before current;
		var pos = this.indexOf(current) + (where == 'before' ? 0 : 1);
		for(var i = this.length-1; i >= pos; i--){
			this[i + 1] = this[i];
		};
		this[pos] = added;
		return this;
	}
	
});

if(Browser.Engine.presto){

	Element.Events.extend({

		contextmenu: {
			base: 'click',
			condition: function(event){ return event.shift;}
		}
		
	});
	
}


/*
---
 
name: Mif.Menu
description: Mif.Menu base class
license: MIT-Style License (http://mifjs.net/license.txt)
copyright: Anton Samoylov (http://mifjs.net)
authors: Anton Samoylov (http://mifjs.net)
requires: 
  - Mif.Core
provides: Mif.Menu
 
...
*/

Mif.Menu=new Class({
	
	version: '1.2',

	Implements: [Events, Options],
	
	options: {
		offsets: {
			x: 0,
			y: 0
		},
		limits: {
			top: 10,
			bottom: 20
		},
		minWidth: 200,
		submenuShowDelay: 300,
		submenuOffsets: {
			x: -2,
			y: -4
		}
	},

	initialize: function(options){
		this.setOptions(options);
		this.element = new Element('div', {'class': 'mif-menu'}).inject(document.body).setStyle('margin-left', -5000);
		this.items = [];
		this.hidden = true;
		this.group = {};
		this.UID = ++Mif.UID;
		Mif.uids[this.UID] = this;
		this.element.setAttribute('uid', this.UID);
		if(this.options.id){
			Mif.ids[this.options.id] = this;
		}
		this.events();
		if(Mif.Menu.KeyNav) new Mif.Menu.KeyNav(this);
	},
	
	show: function(coords){
		if(coords && coords.event) coords.preventDefault();
		this.hidden = false;
		if(!this.items.length) return this;
		if(!this.$draw) this.draw();
		this.element.setStyle('margin-left', 0);
		this.updateWidth();
		this.position(coords);
		this.addHideOnExtraClick();
		this.time = $time();
		this.focus();
		return this.fireEvent('show');
	},
	
	hide: function(){
		if(this.hidden || !this.$draw) return;
		this.hidden = true;
		this.unselect();
		this.element.removeClass('left').removeClass('right');
		this.wrapper.setStyle('height', 'auto');
		this.top.setStyle('display', 'none');
		this.bottom.setStyle('display', 'none');
		this.element.setStyle('margin-left', -5000);
		this.hideSubmenu();
		if(this.parentItem){
			var menu = this.parentItem.menu;
			menu.openSubmenu = false;
			menu.fireEvent('hideSubmenu', this);
		}
		return this.fireEvent('hide');
	},
	
	focus: function(){
		if(Mif.Focus && Mif.Focus == this) return this;
		if(Mif.Focus) Mif.Focus.blur();
		Mif.Focus = this;
		return this.fireEvent('focus');
	},
	
	blur: function(){
		Mif.Focus = null;
		return this.fireEvent('blur');
	},
	
	position: function(coords){
		if(!coords){
			var parent = this.parentItem.getElement();
			if(!parent) return this.hide();
			position = parent.getPosition();
			var size = window.getSize(), scroll = window.getScroll();
			var menu = {x: this.element.offsetWidth, y: this.element.offsetHeight};
			var props = {x: 'left', y: 'top'};
			var coords = {};
			//x
			var side = 'right';
			var pos = position.x + parent.offsetWidth + this.options.submenuOffsets.x;
			if ((pos + menu.x - scroll.x) > size.x){
				side = 'left';
				pos = position.x - menu.x - this.options.submenuOffsets.x;
			}
			coords.x = Math.max(0, pos);
			this.element.addClass(side);
			//y
			var pos = position.y + this.options.submenuOffsets.y;
			var delta = (pos + menu.y - scroll.y) - (size.y - this.options.limits.bottom);
			if (delta > 0){
				pos -= delta;
			};
			coords.y = Math.max(this.options.limits.top, pos);
			var delta = size.y - this.options.limits.bottom - (coords.y - scroll.y);
			if(delta < this.wrapper.offsetHeight){
				this.setHeight(delta - (this.element.offsetHeight - this.wrapper.offsetHeight));
			}
		}else{
			if(coords.event) coords = coords.page;
			var y = coords.y;
			var size = window.getSize(), scroll = window.getScroll();
			var menu = {x: this.element.offsetWidth, y: this.element.offsetHeight};
			var props = {x: 'left', y: 'top'};
			//x
			var pos = coords.x + this.options.offsets.x;
			if ((pos + menu.x - scroll.x) > size.x) pos = coords.x - this.options.offsets.x - menu.x;
			coords.x = Math.max(0, pos);
			//y
			var pos = coords.y + this.options.offsets.y;
			var delta = (pos + menu.y - scroll.y) - (size.y - this.options.limits.bottom);
			if (delta > 0){
				if(this.element.offsetHeight - delta > this.wrapper.getStyle('min-height').toInt()*3){
					this.setHeight(this.element.offsetHeight - delta - (this.element.offsetHeight - this.wrapper.offsetHeight));
				}else{
					pos = coords.y - this.options.offsets.y - menu.y;
				}
			}
			coords.y = Math.max(this.options.limits.top, pos);
			var delta = coords.y + this.element.offsetHeight - y;
			if(coords.y < y && delta > 0){
				this.setHeight(y - coords.y - (this.element.offsetHeight - this.wrapper.offsetHeight));
			}
		}
		this.element.setPosition(coords);
		return this;
	},
	
	addHideOnExtraClick: function(){
		document.addEvent('mousedown', this.bound.hideOnExtraClick);
	},
	
	hideOnExtraClick: function(event){
		var target = document.id(event.target);
		var wrapper = this.wrapper;
		if(wrapper.hasChild(target) || wrapper == target) return;
		var menu = target.getAncestor('.mif-menu');
		if(menu && menu != this.element) return;
		this.hide();
		document.removeEvent('mousedown', this.bound.hideOnExtraClick); 
	},
	
	events: function(){
		this.bound={
			close: this.close.bind(this),
			hover: this.hover.bind(this),
			show: this.show.bind(this),
			hideOnExtraClick: this.hideOnExtraClick.bind(this)
		};
		this.element.addEvents({
			mouseover: this.bound.hover,
			mouseout: this.bound.hover,
			click: this.bound.close
		});
	},
	
	hover: function(event){
		if(this.hidden) return;
		var target = $(event.target);
		var itemEl = target.getAncestor('.mif-menu-item');
		if(!itemEl) return this.unselect();
		var item = Mif.uids[itemEl.getAttribute('uid')];
		if(event.type == 'mouseout' && event.relatedTarget && !$(event.relatedTarget).getAncestor('.mif-menu-item') && (item.submenu ? this.openSubmenu != item.submenu : true)) return this.unselect();
		if(item.get('disabled')) return this.unselect();
		if(this.hovered == item) return;
		this.select(item);
		if(item.submenu || item.get('hasSubmenu')){
			this.showSubmenu(item);
		}
	},
	
	select: function(item){
		this.unselect();
		this.hovered = item;
		this.hovered.getElement().addClass('hover');
		this.makeVisible(item);
		this.fireEvent('hover', ['over', this.hovered]);
	},
	
	unselect: function(){
		if(!this.hovered) return;
		var item = this.hovered;
		$clear(item.timer);
		var el = this.hovered.getElement();
		if(el) el.removeClass('hover');
		this.fireEvent('hover', ['out', this.hovered]);
		this.hideSubmenu();
		this.hovered = null;
	},
	
	showSubmenu: function(item, delay){
		var self = this;
		item.timer = function(){
			if(!item.submenu){
				item.addEvent('load', function(){
					if(item == item.menu.hovered && !item.menu.hidden) self.showSubmenu(item, 0);
					item.removeEvent('load', arguments.callee);
				});
				item.load();
				return;
			}
			var submenu = item.submenu;
			var menu = item.menu;
			menu.blur();
			submenu.show();
			menu.openSubmenu = submenu;
			item.timer=null;
			menu.fireEvent('showSubmenu', submenu);
		}.delay($pick(delay, this.options.submenuShowDelay));
	},
	
	hideSubmenu: function(){
		if(!this.openSubmenu) return;
		var item = this.openSubmenu.parentItem;
		$clear(item.timer);
		item.submenu.hide();
		item.menu.focus();
	},
	
	close: function(event){
		var item;
		if(event.event){
			var target = document.id(event.target);
			var itemEl = target.getAncestor('.mif-menu-item');
			if(itemEl) item = Mif.uids[itemEl.getAttribute('uid')];
		}else{
			item = event;
		};
		if(item){
			item.action();
			$clear(item.timer);
			item.timer = null;
		};
		this.hide();
		var parentItem = this.parentItem;
		while(parentItem){
			parentItem.menu.hide();
			parentItem = parentItem.menu.parentItem;
		}
		return this;
	},
	
	attach: function(target){
		this.target = document.id(target);
		this.target.addEvent('contextmenu', this.bound.show);
		return this;
	},
	
	detach: function(){
		if(this.target) this.target.removeEvent('contextmenu', this.bound.show);
		return this;
	}
	
});


/*
---
 
name: Mif.Menu.Item
description: menu item class
license: MIT-Style License (http://mifjs.net/license.txt)
copyright: Anton Samoylov (http://mifjs.net)
authors: Anton Samoylov (http://mifjs.net)
requires: 
  - Mif.Menu
provides: Mif.Menu.Item
 
...
*/

Mif.Menu.Item=new Class({

	Implements: [Events],
	
	defaults: {
		name: ''
	},

	initialize: function(structure, property){
		if(typeof property == 'string') {
			if(property == '-'){
				property = {sep: true};
			}else{
				property = {desc: property};
			}
		};
		this.property = {};
		$extend(this.property, this.defaults);
		$extend(this.property, property);
		$extend(this, structure);
		var group = this.property.group;
		if(group){
			this.menu.group[group] = this.menu.group[group] || [];
			this.menu.group[group].push(this);
		};
		this.UID = ++Mif.UID;
		Mif.uids[this.UID] = this;
		var id = this.get('id');
		if(id != null) Mif.ids[id] = this;
		this.menu.fireEvent('itemCreate', [this]);
	},
	
	get: function(prop){
		return this.property[prop];
	},
	
	set: function(obj){
		if(arguments.length == 2){
			var object = {};
			object[arguments[0]] = arguments[1];
			obj = object;
		};
		this.menu.fireEvent('beforeSet', [this, obj]);
		var property = obj || {};
		for(var p in property){
			var nv = property[p];
			var cv = this[p];
			this.updateProperty(p, cv, nv);
			this.property[p] = nv;
		};
		this.menu.fireEvent('set', [this, obj]);
		return this;
	},
	
	updateProperty: function(p, cv, nv){
		if(nv == cv) return this;
		if(p == 'id'){
			delete Mif.ids[cv];
			if(nv) Mif.ids[nv] = this;
			return this;
		}
		if(!this.menu.isUpdatable(this)) return this;
		switch(p){
			case 'name':
				this.getElement('name').set('html', nv);
				this.menu.updateWidth();
				return this;
			case 'cls':
				this.getElement().removeClass(cv).addClass(nv);
				return this;
			case 'icon':
				var iconEl = this.getElement('icon');
				if(iconEl) iconEl.dispose();
				if(!nv) return this;
				if(nv.indexOf('/') == -1 && nv.substring(0, 1) == '.'){
					iconEl = new Element('span').addClass(nv.substring(1));
				}else{
					iconEl = new Element('img').setProperty('src', nv);
				};
				iconEl.addClass('mif-menu-icon').inject(this.getElement('name'), 'before');
				return this;
			case 'disabled':
				this.getElement()[(nv ? 'add' : 'remove') + 'Class']('disabled');
				if(nv && this.menu.hovered == this) this.menu.unselect();
				return this;
			case 'hidden':
				var height = this.getElement().offsetHeight;
				var offsetHeight = this.menu.wrapper.offsetHeight;
				var scrollHeight = this.menu.wrapper.scrollHeight;
				this.getElement().setStyle('display', nv ? 'none' : 'block');
				if(scrollHeight - height < offsetHeight){
					this.menu.setHeight(scrollHeight - height);
				}else{
					this.menu.setHeight(this.menu.wrapper.offsetHeight);
				}
				return this;
		}
	},
	
	action: function(){
		if(this.get('disabled')) return this;
		var action = this.property.action;
		if(action){
			if(typeof action == 'string'){
				action = eval('(' + action + ')');
				this.property.action = action;
			} ;
			action.call(this.menu, this);
		};
		this.menu.fireEvent('action', [this]);
		if(this.get('checked') != undefined && (this.get('group') ? !this.property.checked : true)) this.check();
		return this;
	},
	
	check: function(state){
		if(this.property.checked == state) return this;
		var group = this.get('group');
		if(!this.property.checked && group){
			this.menu.group[group].each(function(item){
				item.check(false);
			});
		};
		this.property.checked = !this.property.checked;
		var el = this.getElement('check');
		if(el) el[(this.property.checked ? 'add' : 'remove') + 'Class']('mif-menu-checked');
		if(!(group && !this.property.checked)){
			var check = this.property.check;
			if(check){
				if(typeof check == 'string'){
					check = eval('(' + check + ')');
					this.property.check = check;
				};
				check.call(this.menu, this, this.property.checked);
			};
		};
		this.menu.fireEvent('check', [this, this.property.checked]);
	}
		
});


/*
---
 
name: Mif.Menu.Draw
description: menu html scructure
license: MIT-Style License (http://mifjs.net/license.txt)
copyright: Anton Samoylov (http://mifjs.net)
authors: Anton Samoylov (http://mifjs.net)
requires: 
  - Mif.Menu
provides: Mif.Menu.Draw
 
...
*/

Mif.Menu.implement({
	
	draw: function(){
		var html = [];
		html.push(this.drawBackground());
		html.push('<div class="mif-menu-scroll mif-menu-scroll-top"></div>');
		html.push('<div class="mif-menu-wrapper">');
			for(var i = 0, l = this.items.length; i < l; i++){
				var item = this.items[i];
				this.getHTML(item, html);
			}
		html.push('</div>');
		html.push('<div class="mif-menu-scroll mif-menu-scroll-bottom"></div>');
		this.element.innerHTML = html.join('');
		this.$draw = true;
		this.wrapper = this.element.getElement('.mif-menu-wrapper');
		this.initScroll();
		this.fireEvent('draw');
	},
	
	drawItem: function(item){
		var el = new Element('div').set('html', this.getHTML(item).join('')).getFirst();
		item.element = el;
		return el;
	},
	
	getHTML: function(item, html){
		html = html || [];
		var icon = item.get('icon');
		var iconCls = '';
		if(icon){
			if(icon.indexOf('/') == -1 && icon.substring(0, 1) == '.'){
				iconCls = icon.substring(1);
			}
		};
		if(item.get('sep')){
			html.push('<div class="mif-menu-sep" uid="' + item.UID + '"></div>');
			return html;
		};
		if(item.get('desc')){
			html.push('<div class="mif-menu-desc" uid="' + item.UID + '">' + item.get('desc') + '</div>');
			return html;
		};
		html.push('<div class="mif-menu-item ' + item.get('cls') + (item.get('disabled') ? ' disabled' : '') + '" uid="' + item.UID + '" id="mif-menu-item-' + item.UID + '"' + (item.get('hidden') ? ' style="display:none"' : '') + '>'+
			(item.get('checked') != undefined ? 
			'<span class="mif-menu-check' + (item.get('group') ? ' mif-menu-check-group' : '') + (item.get('checked') ? ' mif-menu-checked"' : '"') + '></span>' : 
			'') +
			(icon ? 
				(iconCls ? '<span class="mif-menu-icon ' + iconCls + '"></span>' : 
					'<img class="mif-menu-icon" src="' + icon + '"></img>'
				) 
			: '') +
			'<span class="mif-menu-name">' + item.get('name') + '</span>'+
			( (item.submenu && item.submenu.items.length) || (!item.get('loaded') && item.get('hasSubmenu')) ? '<span class="mif-menu-submenu"></span>' : '')+
		'</div>');
		return html;
	},
	
	drawBackground: function(){
		return '<div class="mif-menu-bg">\
					<div class="top">\
						<div class="tl"></div>\
						<div class="t"></div>\
						<div class="tr"></div>\
					</div>\
					<div class="center">\
						<div class="l"></div>\
						<div class="c"></div>\
						<div class="r"></div>\
					</div>\
					<div class="bottom">\
						<div class="bl"></div>\
						<div class="b"></div>\
						<div class="br"></div>\
					</div>\
				</div>';
	},
	
	isUpdatable: function(item){
		return !!item.getElement();
	},
	
	updateInject: function(item, element){
		if(!this.$draw) return;
		element = element || item.getElement() || this.drawItem(item);
		var index = this.items.indexOf(item);
		var previous = index > 0 ? this.items[index - 1].getElement() : null;
		if(previous){
			element.inject(previous, 'after');
		}else{
			element.inject(this.wrapper, 'top');
		}
		return this;
	},
	
	updateWidth: function(){
		this.element.setStyle('width', 'auto').dispose().inject(document.body);
		var width = Math.max(this.element.offsetWidth, parseInt(this.options.minWidth));
		this.element.setStyle('width', width);
		return this;
	}
	
});

Mif.Menu.Item.implement({

	getElement: function(type){
		var item = this.element || document.id('mif-menu-item-'+this.UID);
		if(!type || !item) return item;
		return item.getElement('.mif-menu-' + type);
	}
	
});

/*
---
 
name: Mif.Menu.Load
description: menu json loader
license: MIT-Style License (http://mifjs.net/license.txt)
copyright: Anton Samoylov (http://mifjs.net)
authors: Anton Samoylov (http://mifjs.net)
requires: 
  - Mif.Menu
provides: Mif.Menu.Load
 
...
*/

Mif.Menu.Load={
		
	menu: function(items, parent, menu){
		if(!(menu instanceof Mif.Menu)){
			var options = {};
			if(items.length && items[0].options){
				options = items[0].options;
				items.erase(items[0]);
			}
			for(var p in options){
				if(/^on([A-Z])/.test(p)){
					if(typeof options[p] == 'string') options[p] = eval('(' + options[p] + ')');
				}
			}
			menu = new Mif.Menu(options);
			parent.submenu = menu;
		}
		menu.parentItem = parent;
		for( var i = items.length; i--; ){
			var item = items[i];
			var submenu = item.submenu;
			var item = new Mif.Menu.Item({
				menu: menu
			}, item);
			menu.items.unshift(item);
			if(submenu && submenu.length){
				arguments.callee(submenu, item, submenu);
			}
		}
	}
	
};

Mif.Menu.implement({

	load: function(options){
		var menu = this;
		var type = $type(options);
		if(type == 'array'){
			options = {json: options}; 
		};
		if(type == 'string'){
			options = {url: options};
		};
		this.loadOptions = this.loadOptions || $lambda({});
		function success(json){
			Mif.Menu.Load.menu(json, null, menu);
			menu.fireEvent('load');
			return menu;
		}
		options = $extend($extend({
			isSuccess: $lambda(true),
			secure: true,
			onSuccess: success,
			method: 'get'
		}, this.loadOptions()), options);
		if(options.json) return success(options.json);
		new Request.JSON(options).send();
		return this;
	}
	
});

Mif.Menu.Item.implement({
	
	load: function(options){
		this.$loading = true;
		var type = $type(options);
		if(type == 'array'){
			options = {json: options}; 
		};
		if(type == 'string'){
			options = {url: options};
		};
		options = options || {};
		var self = this;
		var el = this.getElement();
		var loader, sub;
		if(el){
			loader = new Element('span', {'class': 'mif-menu-loader'}).inject(el);
			sub = el.getElement('.mif-menu-submenu').dispose();
		}
		function success(json){
			Mif.Menu.Load.menu(json, self, json);
			delete self.$loading;
			self.property.loaded = true;
			if(loader){
				loader.dispose();
				if(self.submenu.items.length) sub.inject(el);
			};
			self.fireEvent('load');
			self.menu.fireEvent('loadItem', self);
			return self;
		}
		options = $extend($extend($extend({
			isSuccess: $lambda(true),
			secure: true,
			onSuccess: success,
			method: 'get'
		}, this.menu.loadOptions(this)), this.property.loadOptions), options);
		if(options.json) return success(options.json);
		new Request.JSON(options).send();
		return this;
	}
	
});


/*
---
 
name: Mif.Menu.Scroll
description: scroll menu
license: MIT-Style License (http://mifjs.net/license.txt)
copyright: Anton Samoylov (http://mifjs.net)
authors: Anton Samoylov (http://mifjs.net)
requires: 
  - Mif.Menu
provides: Mif.Menu.Scroll
 
...
*/

Mif.Menu.implement({
	
	initScroll: function(){
		var self = this;
		var top = this.element.getElement('.mif-menu-scroll-top');
		var bottom = this.element.getElement('.mif-menu-scroll-bottom');
		this.top = top;
		this.bottom = bottom;
		this.scroll = new Fx.Scroll(this.wrapper, {
			link: 'cancel',
			onComplete: function(){
				top.setStyle('display', this.element.scrollTop == self.items[0].getElement().offsetTop ? 'none' : 'block');
				bottom.setStyle('display', this.element.scrollTop == this.element.scrollHeight - this.element.clientHeight ? 'none' : 'block');
			}
		});
		$extend(this.bound, {
			startScrollBottom: this.startScrollBottom.bind(this),
			startScrollTop: this.startScrollTop.bind(this),
			stopScroll: this.stopScroll.bind(this)
		});
		bottom.addEvents({
			mouseenter: this.bound.startScrollBottom,
			mouseleave: this.bound.stopScroll
		});
		top.addEvents({
			mouseenter: this.bound.startScrollTop,
			mouseleave: this.bound.stopScroll
		});
		this.bound.mousewheel = this.mousewheel.bind(this);
		this.element.addEvent('mousewheel', this.bound.mousewheel);
	},
	
	mousewheel: function(event){
		var delta = event.wheel;
		this.doScroll(delta);
	},
	
	startScroll: function(side){
		if(this.scrollTimer) return;
		var startTime = 0;
		var startTop = this.element.getPosition().y;
		var self = this;
		var wrapper = this.wrapper;
		var startHeight = wrapper.offsetHeight;
		var startScrollTop = wrapper.scrollTop;
		var scrollingTime = 0;
		this.scrollTimer = (function(){
			if($time() - self.time < 1500) return;
			if(!startTime) startTime = $time();
			var delta = ($time() - startTime)/50*10;
			if(!delta) return;
			if(side == 'top') delta = -delta;
			if(!self.scrollMove(delta)){
				$clear(self.scrollTimer);
				self.srollTimer = null;
			}else{
				startTime = $time();
			}
		}).periodical(50);
	},
	
	startScrollBottom: function(){
		this.startScroll('bottom');
	},
	
	startScrollTop: function(){
		return this.startScroll('top');
	},
	
	stopScroll: function(){
		$clear(this.scrollTimer);
		this.scrollTimer = null;
	},
	
	doScroll: function(delta){
		this.scrollMove(-10*delta);
	},
	
	scrollMove: function(delta){
		var side = delta < 0 ? 'top' : 'bottom';
		var wrapper = this.wrapper;
		var offsetTop = this.element.offsetTop;
		if(side == 'bottom'){
			if(wrapper.scrollTop == wrapper.scrollHeight - wrapper.clientHeight) return;
			var limit = this.options.limits.top;
			var top = offsetTop - delta;
			if(top < limit){
				this.element.setStyle('top', limit);
				wrapper.setStyle('height', Math.min(wrapper.scrollHeight, wrapper.offsetHeight + offsetTop - limit));
				wrapper.scrollTop = wrapper.scrollTop + delta;
			}else{
				var height = wrapper.offsetHeight + delta;
				if(height > wrapper.scrollHeight){
					wrapper.setStyle('height', 'auto');
					this.element.setStyle('top', offsetTop - delta + (height - wrapper.scrollHeight));
				}else{
					wrapper.setStyle('height', height);
					this.element.setStyle('top', offsetTop - delta);
				}
			}
		}else{
			if(wrapper.scrollTop == 0) return;
			var bottom = window.getSize().y - (offsetTop + wrapper.offsetHeight - delta - window.getScroll().y) - (this.element.offsetHeight - this.wrapper.offsetHeight);
			var limit = this.options.limits.bottom;
			if(bottom < limit){
				wrapper.setStyle('height', Math.min(wrapper.scrollHeight, window.getSize().y - limit - (offsetTop - window.getScroll().y + (this.element.offsetHeight - this.wrapper.offsetHeight))));
				wrapper.scrollTop = wrapper.scrollTop + delta;
			}else{
				var height = wrapper.offsetHeight - delta;
				wrapper.scrollTop = wrapper.scrollTop + delta;
				if(height > wrapper.scrollHeight){
					wrapper.setStyle('height', 'auto');
				}else{
					wrapper.setStyle('height', height);
				}
			}
		};
		var result = true;
		if(wrapper.scrollTop == 0){
			this.top.setStyle('display', 'none');
			if(side == 'top') result = false;
		}else{
			this.top.setStyle('display', 'block');
		};
		if(wrapper.scrollTop == wrapper.scrollHeight - wrapper.offsetHeight){
			this.bottom.setStyle('display', 'none');
			if(side == 'bottom') result = false;
		}else{
			this.bottom.setStyle('display', 'block');
		}
		return result;
	},
	
	makeVisible: function(item){
		var el = item.getElement();
		var offsetTop = el.offsetTop;
		var offsetBottom = offsetTop + el.offsetHeight;
		var top = this.top.offsetHeight;
		var bottom = this.bottom.offsetHeight;
		var wrapper = this.wrapper;
		var wrapperTop = wrapper.scrollTop + top;
		var wrapperBottom = wrapperTop + wrapper.offsetHeight - bottom;
		if(offsetTop < wrapperTop){
			this.scrollMove(offsetTop - wrapperTop);
		}else if(offsetBottom > wrapperBottom){
			this.scrollMove(offsetBottom - wrapperBottom + top);
		}
	},
	
	setHeight: function(height){
		var wrapper = this.wrapper;
		if(height >= wrapper.scrollHeight) {
			wrapper.setStyle('height', 'auto');
		}else{
			wrapper.setStyle('height', height);
		};
		this.top.setStyle('display', wrapper.scrollTop == 0 ? 'none' : 'block');
		this.bottom.setStyle('display', wrapper.scrollTop == wrapper.scrollHeight - wrapper.clientHeight ? 'none' : 'block');
		return this;
	}
	
});


/*
---
 
name: Mif.Menu.KeyNav
description: keyboard navigation using up/left/up/down/esc/enter
license: MIT-Style License (http://mifjs.net/license.txt)
copyright: Anton Samoylov (http://mifjs.net)
authors: Anton Samoylov (http://mifjs.net)
requires: 
  - Mif.Menu
provides: Mif.Menu.KeyNav
 
...
*/

Mif.Menu.KeyNav = new Class({
	
	initialize: function(menu){
		this.menu = menu;
		menu.keynav = this;
		this.bound = {
			attach: this.attach.bind(this),
			detach: this.detach.bind(this),
			keyaction: this.keyaction.bind(this)
		};
		this.keyevent = (Browser.Engine.presto || Browser.Engine.gecko) ? 'keypress' : 'keydown';
		menu.addEvent('focus', this.bound.attach);
		menu.addEvent('blur', this.bound.detach);
	},
	
	attach: function(){
		document.addEvent(this.keyevent, this.bound.keyaction);
	},
	
	detach: function(){
		document.removeEvent(this.keyevent, this.bound.keyaction);
	},
	
	keyaction: function(event){
		if(!['down','left','right','up','enter', 'esc'].contains(event.key)) return;
			if(event.key == 'esc'){
				this.menu.hide();
				return;
			};
			var current = this.menu.hovered;
			switch (event.key){
				case 'down': this.goForward(current); break;  
				case 'up': this.goBack(current); break;   
				case 'left': this.goLeft(current); break;
				case 'right': this.goRight(current); break;
				case 'enter': this.action(current, event);
			}
			return false;
	},

	goForward: function(current){
		var menu = this.menu;
		var items = this.menu.items;
		while(1){
			var index = items.indexOf(current);
			if(index == items.length - 1) return;
			current = items[index + 1];
			if(!current) return;
			if(!current.get('disabled') && !current.get('hidden') && !current.get('sep') && !current.get('desc')) break;
		};
		menu.select(current);
	},
	
	goBack: function(current){
		var menu = this.menu;
		var items = this.menu.items;
		while(1){
			var index = items.indexOf(current);
			if(index == 0) return;
			if(index == -1) index = items.length;
			current = items[index - 1];
			if(!current) return;
			if(!current.get('disabled') && !current.get('hidden') && !current.get('sep') && !current.get('desc')) break;
		};
		menu.select(current);
	},
	
	goLeft: function(current){
		if(this.menu.parentItem){
			this.menu.parentItem.menu.hideSubmenu();
		};
	},
	
	goRight: function(current){
		if(!current) return;
		var submenu = current.submenu;
		if(!submenu) return;
		var menu = this.menu;
		menu.showSubmenu(current, 0);
		submenu.keynav.goForward();
	},
	
	action: function(current){
		current.menu.close(current);
	}
	
});


/*
---
 
name: Mif.Menu.Item
description: implement methods for change menu structure(add/move/remove..)
license: MIT-Style License (http://mifjs.net/license.txt)
copyright: Anton Samoylov (http://mifjs.net)
authors: Anton Samoylov (http://mifjs.net)
requires: 
  - Mif.Menu
provides: Mif.Menu.Transform
 
...
*/

Mif.Menu.Item.implement({

	inject: function(current, where){
		where = where || 'after';
		this.menu.items.erase(this);
		current.menu.items.inject(this, current, where);
		if(!this.menu.items.length){
			this.menu.hide();
			if(this.menu.parentItem){
				var submenuEl = this.menu.parentItem.getElement('submenu');
				if(submenuEl) submenuEl.dispose();
			}
		}
		if(current.menu.items.length == 1){
			var item = current.menu.parentItem;
			if(item){
				var el = item.getElement();
				if(el){
					new Element('span', {'class': 'mif-menu-submenu'}).inject(el);
				}
			}
		}
		this.menu = current.menu;
		current.menu.updateInject(this);
		return this;
	},
	
	copy: function(item, where){
		if (this.get('copyDenied')) return;		
		var itemCopy = new Mif.Menu.Item({
			menu: item.menu
		}, $unlink(this.property));
		return itemCopy.inject(item, where);
	},
	
	remove: function(){
		this.menu.fireEvent('beforeRemove', [this]);
		this.menu.items.erase(this);
		var element = this.getElement();
		if(element) element.dispose();
		if(this.submenu) this.submenu.hide();
		if(!this.menu.items.length){
			this.menu.hide();
			if(this.menu.parentItem){
				var submenuEl = this.menu.parentItem.getElement('submenu');
				if(submenuEl) submenuEl.dispose();
			}
		}
		this.menu.fireEvent('remove', [this]);
	}
	
});


Mif.Menu.implement({
	
	move: function(from, to, where){
		where = where || 'after';
		if(from.inject(to, where)){
			this.fireEvent('move', [from, to, where]);
		}
		return this;
	},
	
	copy: function(from, to, where){
		var copy = from.copy(to, where);
		if(copy){
			this.fireEvent('copy', [from, to, where, copy]);
		}
		return this;
	},
	
	remove: function(item){
		item.remove();
		return this;
	},

	add: function(item, current, where){
		where = where || 'after';
		if(!(item instanceof Mif.Menu.Item)){
			item = new Mif.Menu.Item({menu: this}, item);
		}
		if($type(current) == 'number') current = this.items[current];
		if(current) {
			item.inject(current, where);
		}else{
			this.items[where == 'top' ? 'unshift' : 'push'](item);
			var el = item.getElement();
			if(el && this.$draw){
				el.inject(this.element, where);
			}
			if(this.parentItem){
				el = this.parentItem.getElement();
				if(el){
					new Element('span', {'class': 'mif-menu-submenu'}).inject(el);
				}
			}
		};
		this.fireEvent('add', [item, current, where]);
		return this;
	},
	
	connect: function(item){
		this.disconnect(item);
		this.parentItem = item;
		item.submenu = this;
		var el = item.getElement();
		if(el){
			new Element('span', {'class': 'mif-menu-submenu'}).inject(el);
		}
		this.fireEvent('connect', [item]);
		return this;
	},
	
	disconnect: function(){
		var parentItem = this.parentItem;
		if(!parentItem) return this;
		parentItem.submenu.hide();
		parentItem.submenu = null;
		var submenuEl = parentItem.getElement('submenu');
		if(submenuEl) submenuEl.dispose();
		this.fireEvent('disconnect', [parentItem]);
		return this;
	}
	
});


