/**
* @version $Id$
* @package Abricos
* @copyright Copyright (C) 2010 Abricos. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

var Component = new Brick.Component();
Component.requires = {
	yahoo: ['datasource','tabview','resize'],
	mod:[
	     {name: 'sys', files: ['data.js', 'form.js', 'container.js', 'wait.js', 'widgets.js']},
	     {name: 'eshop', files: ['order.js']}
	]
};
Component.entryPoint = function(){
	var Dom = YAHOO.util.Dom,
		E = YAHOO.util.Event,
		L = YAHOO.lang;
	
	var NS = this.namespace,
		API = NS.API,
		TMG = this.template;
	
	NS['billing'] = NS['billing'] || {}; 
	
	if (!NS.data){
		NS.data = new Brick.util.data.byid.DataSet('eshop');
	}
	var DATA = NS.data;
	
	var LW = Brick.widget.LayWait;

	var buildTemplate = function(widget, templates){
		var TM = TMG.build(templates), T = TM.data, TId = TM.idManager;
		widget._TM = TM; 
		widget._T = T; 
		widget._TId = TId;
	};

	API.formatPrice = function(price){
		var fprice = YAHOO.util.Number.format(price, {
			decimalPlaces: 2,
			thousandsSeparator: ' ',
			suffix: ''
		}); 
		return fprice; 
	};
	
	var Manager = function(container){
		this.init(container);
	};
	
	Manager.prototype = {
		init: function(container){
			buildTemplate(this, 'widget');
			container.innerHTML = this._T['widget'];
			
			var tabView = new YAHOO.widget.TabView(this._TM.getElId('widget.id'));
			this.page = {
				'new': new OrderListNew(this, this._TM.getElId('widget.new')),
				'exec': new OrderListExec(this, this._TM.getElId('widget.exec')),
				'arhive': new OrderListArhive(this, this._TM.getElId('widget.arhive')),
				'recycle': new OrderListRecycle(this, this._TM.getElId('widget.recycle'))
			};
		},
		onClick: function(el){
			return false;
		},
		orderAccept: function(orderid){
			var page = this.page;
			Brick.ajax('eshop',{
				'data': {'do': 'order-accept', 'orderid': orderid},
				'event': function(request){
					page['new'].refresh(true);
					page['exec'].refresh();
				}
			});
		},
		orderRemove: function(orderid){
			var page = this.page;
			Brick.ajax('eshop',{
				'data': {'do': 'order-remove', 'orderid': orderid},
				'event': function(request){
					page['new'].refresh(true);
					page['recycle'].refresh();
				}
			});
		},
		orderClose: function(orderid){
			var page = this.page;
			Brick.ajax('eshop',{
				'data': {'do': 'order-close', 'orderid': orderid},
				'event': function(request){
					page['exec'].refresh(true);
					page['arhive'].refresh();
				}
			});
		}
	};
	NS.billing.Manager = Manager;
	
	API.showBillingManagerWidget = function(container){
		new Manager(container);
		DATA.request();
	};
	
	var OrderList = function(owner, container, type, extButtonsCount){
		extButtonsCount = 1;
		
		this.owner = owner;
		this.listType = type;
		this.extButtonsCount = extButtonsCount;
		var extt = extButtonsCount > 0 ? ',btnh'+type+',btn'+type : '';
		
		buildTemplate(this, 'list,table,row,rowwait,usertpname,usertpip'+extt);
	
		OrderList.superclass.constructor.call(this, container, {
			tm: this._TM, DATA: DATA, rowlimit: 10,
			tables: { 'list': 'orders-'+type, 'count': 'orderscnt-'+type },
			paginators: ['list.pagtop', 'list.pagbot']
		});    
	};
    YAHOO.extend(OrderList, Brick.widget.TablePage, {
    	initTemplate: function(){
			return this._T['list'];
		},
    	renderTableAwait: function(){
			var TM = this._TM, T = this._T;
    		TM.getEl("list.table").innerHTML = TM.replace('table', {
    			'btns': this.extButtonsCount > 0 ? T['btnh'+this.listType] : '',
    			'rows': T['rowwait']
    		});
    	},
		renderRow: function(di){
			var TM = this._TM, T = this._T;
			
			var user = "";
			if (di['uid'] > 0){
				user = TM.replace('usertpname', {'uid': di['uid'], 'unm': di['unm']});
			}else{
				user = TM.replace('usertpip', { 'uid': di['uid'], 'unm': di['ip']});
			}
			
    		return TM.replace('row', {
    			'btns': this.extButtonsCount > 0 ? T['btn'+this.listType] : '',
    			'fnm': di['fnm'],
    			'lnm': di['lnm'],
    			'adr': di['adr'],
    			'ip': di['ip'],
    			'dl': Brick.dateExt.convert(di['dl']),
    			'sm': API.formatPrice(di['sm']),
    			'id': di['id']
			});
    	},
    	renderTable: function(lst){
			var TM = this._TM, T = this._T;
    		TM.getEl("list.table").innerHTML = TM.replace('table', {
    			'btns': this.extButtonsCount > 0 ? T['btnh'+this.listType] : '',
    			'rows': lst
    		});
    	}, 
		onClick: function(el){
			return false;
		}
	});
    
	var OrderListNew = function(owner, container){
		OrderListNew.superclass.constructor.call(this, owner, container, 'new', 2);
	};
	YAHOO.extend(OrderListNew, OrderList, {
		onClick: function(el){
			var prefix = el.id.replace(/([0-9]+$)/, ''),
				orderid = el.id.replace(prefix, ""),
				tp = this._TId['btnnew'],
				owner = this.owner;
			
			switch(prefix){
			case tp['view']+'-':
			case tp['accept']+'-':
				var owner = this.owner;
				new OrderAcceptPanel(orderid, function(){
					owner.orderAccept(orderid);
				});
				return true;
			case tp['remove']+'-':
				new OrderRemovePanel(orderid, function(){
					owner.orderRemove(orderid);
				});
				return true;
			}
			return false;
		}
	});
	NS.billing.OrderListNew = OrderListNew;

	
	var OrderListExec = function(owner, container){
		OrderListExec.superclass.constructor.call(this, owner, container, 'exec', 1);
	};
	YAHOO.extend(OrderListExec, OrderList, {
		onClick: function(el){
			var prefix = el.id.replace(/([0-9]+$)/, ''),
				orderid = el.id.replace(prefix, ""),
				tp = this._TId['btnexec'],
				owner = this.owner;
			
			switch(prefix){
			case tp['view']+'-':
			case tp['close']+'-':
				var owner = this.owner;
				new OrderClosePanel(orderid, function(){
					owner.orderClose(orderid);
				});
				return true;
			}
			return false;
		}
	});
	NS.billing.OrderListExec = OrderListExec;

	
	var OrderListArhive = function(owner, container){
		OrderListArhive.superclass.constructor.call(this, owner, container, 'arhive');
	};
	YAHOO.extend(OrderListArhive, OrderList, {
		onClick: function(el){
			var prefix = el.id.replace(/([0-9]+$)/, ''),
				orderid = el.id.replace(prefix, ""),
				tp = this._TId['btnarhive'];
			
			switch(prefix){
			case tp['view']+'-':
				new OrderViewPanel(orderid);
				return true;
			}
			return false;
		}
	});
	NS.billing.OrderListArhive = OrderListArhive;

	var OrderListRecycle = function(owner, container){
		OrderListRecycle.superclass.constructor.call(this, owner, container, 'recycle');
	};
	YAHOO.extend(OrderListRecycle, OrderList, {
		onClick: function(el){
			var prefix = el.id.replace(/([0-9]+$)/, ''),
				orderid = el.id.replace(prefix, ""),
				tp = this._TId['btnrecycle'];
			
			switch(prefix){
			case tp['view']+'-':
				new OrderViewPanel(orderid);
				return true;
			}
			return false;
		}
		
	});
	NS.billing.OrderListRecycle = OrderListRecycle;
	
	var OrderAcceptPanel = function(orderid, callback){
		this.orderid = orderid*1;
		this.callback = callback;
		OrderAcceptPanel.superclass.constructor.call(this, {
			fixedcenter: true, 
			modal: true,
			width: '800px',
			resize: true
		});
	};
	
	YAHOO.extend(OrderAcceptPanel, Brick.widget.Panel, {
		initTemplate: function(){
			buildTemplate(this, 'orderacceptpanel');
			return this._T['orderacceptpanel'];
		},
		onLoad: function(){
			var TM = this._TM, T = this._T, TId = this._TId,
				orderid = this.orderid;
			
			this.orderView = new NS.OrderViewWidget(TM.getEl('orderacceptpanel.print'), orderid);
			DATA.request();
		},
		destroy: function(){
			this.orderView.destroy();
			OrderAcceptPanel.superclass.destroy.call(this);
		},
		onClick: function(el){
			var tp = this._TId['orderacceptpanel'];
			switch(el.id){
			case tp['bcancel']: this.close(); return true;
			case tp['baccept']: 
				this.close();
				this.callback('accept', this.orderid);
				return true;
			}
			return false;
		}
	});
    NS.OrderAcceptPanel = OrderAcceptPanel;

	var OrderRemovePanel = function(orderid, callback){
		this.orderid = orderid*1;
		this.callback = callback;
		OrderAcceptPanel.superclass.constructor.call(this, {
			fixedcenter: true, 
			modal: true,
			width: '800px',
			resize: true
		});
	};
	
	YAHOO.extend(OrderRemovePanel, Brick.widget.Panel, {
		initTemplate: function(){
			buildTemplate(this, 'orderremovepanel');
			return this._T['orderremovepanel'];
		},
		onLoad: function(){
			var TM = this._TM, T = this._T, TId = this._TId,
				orderid = this.orderid;
			
			this.orderView = new NS.OrderViewWidget(TM.getEl('orderremovepanel.print'), orderid);
			DATA.request();
		},
		destroy: function(){
			this.orderView.destroy();
			OrderRemovePanel.superclass.destroy.call(this);
		},
		onClick: function(el){
			var tp = this._TId['orderremovepanel'];
			switch(el.id){
			case tp['bcancel']: this.close(); return true;
			case tp['bremove']: 
				this.close();
				this.callback('remove', this.orderid);
				return true;
			}
			return false;
		}
	});
    NS.OrderRemovePanel = OrderRemovePanel;

	var OrderClosePanel = function(orderid, callback){
		this.orderid = orderid*1;
		this.callback = callback;
		OrderClosePanel.superclass.constructor.call(this, {
			fixedcenter: true, 
			modal: true,
			width: '800px',
			resize: true
		});
	};
	
	YAHOO.extend(OrderClosePanel, Brick.widget.Panel, {
		initTemplate: function(){
			buildTemplate(this, 'orderclosepanel');
			return this._T['orderclosepanel'];
		},
		onLoad: function(){
			var TM = this._TM, T = this._T, TId = this._TId;
			this.orderView = new NS.OrderViewWidget(TM.getEl('orderclosepanel.print'), this.orderid);
			DATA.request();
		},
		destroy: function(){
			this.orderView.destroy();
			OrderClosePanel.superclass.destroy.call(this);
		},
		onClick: function(el){
			var tp = this._TId['orderclosepanel'];
			switch(el.id){
			case tp['bcancel']: this.close(); return true;
			case tp['boclose']: 
				this.close();
				this.callback('close', this.orderid);
				return true;
			}
			return false;
		}
	});
    NS.OrderClosePanel = OrderClosePanel;

	var OrderViewPanel = function(orderid, callback){
		this.orderid = orderid*1;
		this.callback = callback;
		OrderViewPanel.superclass.constructor.call(this, {
			fixedcenter: true, 
			modal: true,
			width: '800px',
			resize: true
		});
	};
	
	YAHOO.extend(OrderViewPanel, Brick.widget.Panel, {
		initTemplate: function(){
			buildTemplate(this, 'orderviewpanel');
			return this._T['orderviewpanel'];
		},
		onLoad: function(){
			var TM = this._TM, T = this._T, TId = this._TId;
			this.orderView = new NS.OrderViewWidget(TM.getEl('orderviewpanel.print'), this.orderid);
			DATA.request();
		},
		destroy: function(){
			this.orderView.destroy();
			OrderViewPanel.superclass.destroy.call(this);
		}
	});
    NS.OrderViewPanel = OrderViewPanel;

};
