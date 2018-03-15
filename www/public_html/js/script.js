/**
 |
 |
 */
(function($){
	/**
	 | @var string
	 */
	// var SESSID = (new Date()).toISOString().substring(0, 10); // + Date.now();
    var SESSID = (new Date()).toISOString().substring(0, 10) + Date.now();

	/**
	 |
	 |
	 */
	function Board(type, options) {
		if (!(this instanceof Board)) {
			return new Board(type, options);
		}
		// Format options
		this._options = options = (typeof options == 'object' ? options : {});
		// Type?
		type = (Board.TYPE_P == type) ? type : Board.TYPE_L;
		if (Board.TYPE_P == type) {
			this.cols = 8;
			this.rows = 20;
		} else {
			this.cols = 20;
			this.rows = 8;
		}
	};
	Board.prototype = {
		/**
		 | 
		 */
		_shootCnt: 0,
		/**
		 | Init board (render board with cols and rows)
		 */
		_$board: null,
		/**
		 | Init board (render board with cols and rows)
		 */
		init: function render(container) {
			var html = '<table class="table table-striped table-bordered table-condensed">';
			for (var row = 1; row <= this.rows; row++) {
				html += '<tr class="row' + row + '">';
				for (var col = 1; col <= this.cols; col++) {
					html += '<td class="text-center col' + col + '"><div>';
					html += 	'<small>' + (row + ':' + col) + '</small>';
					html += '</div></td>';
				}
				html += '</tr>';
			}
			html += '</table>';
			$(container).html(this._$board = $(html));
		},
		/**
		 | Render ships
		 | @param Array ships
		 */
		renderShips: function renderShips(ships) {
			ships = (ships instanceof Array) ? ships : [ships];
			for (var ship of (ships || [])) {
				var vector = []; var num = 0;
				for (var row = 0; row < ship.matrix.length; row++) {
					vector = ship.matrix[row];
					for (var col = 0; col < vector.length; col++) {
						num = vector[col];
						if (!num) { continue; }
						var slt = 'tr.row' + (ship.y + row) + ' > td.col' + (ship.x + col);
						var $td = this._$board.find(slt);
						if (!$td.length) {
							alert('Critial error: ship is out of border. Slt: ' + slt + '.');
							return;
						}
						var dataShip = $.trim($td.data('ship'));
						if (dataShip) {
							alert('Critial error: ships overlapped. Slt: ' + slt + '.');
							return;
						}
						$td.attr('data-ship', (ship.name || ship.type));
						// console.log('slt: ', slt, ' - $td: ',  $td.get(0));
					}
				}
			}
		},
		/**
		 | Render shoots
		 | @param Array shoots
		 */
		renderShoots: function renderShoots(shoots) {
			var shoot = null, cnt = 0, $td = null;
			for (var key in (shoots || {})) {
				key = key.split(':');
				var y = key[0], x = key[1];
				cnt = 1 * shoots[key];
				$td = this._styleTdShoot(x, y);
			}
		},
		/**
		 | 
		 */
		shootAt: function shootAt(data) {
			var isHit = 0;
			var $td = this._styleTdShoot(data.x, data.y);
			if ($td && $td.length) {
				var ship = $.trim($td.attr('data-ship'));
				if (ship) { // Ship was destroy?
					isHit = 1;
					var sltShip = 'td[data-ship="' + ship + '"]';
					var sltShoot = 'td[data-shoot]';
					var $tdShips = this._$board.find(sltShip);
					var $tdShoots = $tdShips.filter(sltShoot);
					// console.log(sltShip, sltShoot, $tdShips, $tdShoots);
					// Case: ship down! 
					if ($tdShips.length == $tdShoots.length) {
						var position = [];
						$tdShips.each(function(){
							var $this = $(this), $tr = $this.parent();
							var col = (1 * ($this.attr('class').match(/col(\d+)/) || [])[1] || '0');
							var row = (1 * ($tr.attr('class').match(/row(\d+)/) || [])[1] || '0')
							position.push({ x: col, y: row });
						});
						isHit = {
							'type': ship,
							'position': position
						};
					}
					
				}
				// Game end?
				$tdShips = this._$board.find('td[data-ship]');
				$tdShoots = $tdShips.filter('td[data-shoot]');
				if ($tdShips.length == $tdShoots.length) {
					setTimeout(function(){
						alert('Game end!!!');
					}, 512);
				}
			}
			this._shootCnt += 1;
			console.log('shoot#' + this._shootCnt + ' data: ', data, ' - isHit: ', isHit);
			return isHit;
		},
		/**
		 | 
		 */
		_styleTdShoot: function shootAt(x, y) {
			var slt = 'tr.row' + y + ' > td.col' + x;
			var $td = this._$board.find(slt);
			if ($td && $td.length) {
				var shootCnt = (1 * ($td.attr('data-shoot') || 0)) + 1;
				$td.attr('data-shoot', shootCnt);
			}
			// console.log('_styleTdShoot # slt: ', slt, ' - $td: ',  $td.get(0));
			return $td;
		}
	};
	Board.TYPE_P = 0; //
	Board.TYPE_L = 1; //
	window.Board = Board;

	/**
	 |
	 |
	 */
	function Bot(options) {
		// Format options
		this._options = options = (typeof options == 'object' ? options : {});
		// #end
		if (!(this instanceof Bot)) {
			return new Bot(options);
		}
	};
	Bot.prototype = {
		/**
		 | @param Bot
		 */
		call: function call(type, data, cb) {
			cb = cb || $.noop;
			data = $.extend({ sessionid: SESSID }, data);
			$.post('/?type=' + encodeURIComponent(type), { type: type, data: data }, function(result){
				if (result && (1 * result.status)) {
					cb(null, result.data);
				} else {
					cb(new Error(result.msg));
				}
			}, 'json')
			.fail(function(){
				console.log('post fail: ', arguments);
			});
		},
		/**
		 | 
		 */
		init: function init(data, cb) {
			data = typeof data == 'object' ? data : {};
			this.call('new_game', {}, cb);
		},
		/**
		 | 
		 */
		shoot: function shoot(cb) {
			this.call('shoot', {}, cb);
		}, 
		/**
		 | 
		 */
		shootAt: function shootAt(data, isHit) {
			data = $.extend({ 'is_hit': isHit }, data); 
			this.call('shoot_at', data);
		}, 
		/**
		 | 
		 */
		checkAvail: function checkAvail(data) {
			this.call('check_avail', data);
		}
	};

	/**
	 | Game Engine simulator
	 |
	 */
	var Battleship = Battleship || {
		/**
		 | @var Board
		 */
		_board: null,

		/**
		 | @var Bot
		 */
		_bot: null,
		
		/**
		 | @var Bot
		 */
		requestShoot: function requestShoot() {
			this._bot.shoot();
		},
		
		/**
		 | 
		 */
		init: function init(container, cb) {
			// Format cb
			cb = cb || $.noop;
			// Init, + render board
			var board = this._board = new Board();
			board.init(container);
			// #end

			var bot = this._bot = new Bot();
			//
			bot.init({}, function(err, data){
				console.log('bot init done: ', err, data);
				// OK?
				if (!err) {
					// Render board data!
					board.renderShips(data.ships);
					board.renderShoots(data.shoots);
				}
				// Fire callback
				cb();
			});
		},
		
		/**
		 | 
		 */
		shoot: function shoot(cb) {
			// Format cb
			cb = cb || $.noop;
			var self = this;
			this._bot.shoot(function(err, data){
				if (err) {
					return alert(err);
				}
				//
				var isHit = Battleship._board.shootAt(data);
				//
				self._bot.shootAt(data, isHit);
				// Fire callback
				cb();
			});
		},
		
		/**
		 | 
		 */
		checkAvail: function checkAvail(cb) {
			// Format cb
			cb = cb || $.noop;
			var self = this;
			this._bot.checkAvail(function(err, data){
				if (err) {
					return alert(err);
				}
				//
				console.log('_bot.checkAvail: ', err, data);
				// Fire callback
				cb();
			});
		},
	};
	window.Battleship = Battleship;
	
	// Init elements
	var $grids = $('#grids');
	var $btnNewGame = $('#btn-new_game');
	var $btnShoot = $('#btn-shoot');
	var $btnCheckAvail = $('#btn-check-avail');

	// Start game?!
	function NewGame(){
		/* if (!confirm('Start new game?')) {
			return;
		} */
		Battleship.init($grids.get(0), function(){
			//
			$btnShoot
				.removeClass('disabled')
				.off('click').on('click', function(evt){
					if ($btnShoot.hasClass('disabled')) {
						return;
					}
					$btnShoot.addClass('disabled');
					Battleship.shoot(function(){
						$btnShoot.removeClass('disabled');
					});
				})
			;
			// #end
		});
	};
	
	//
	$btnNewGame.click(function(){ 
		// NewGame();
		window.location.reload();
	});
	//
	$btnCheckAvail.click(function(){
		Battleship.checkAvail();
	});
	//
	setTimeout(NewGame, 256);
})(jQuery);