/**
 |
 |
 */
(function($){
	/**
	 * Returns a random integer between min (inclusive) and max (inclusive)
	 * Using Math.round() will give you a non-uniform distribution!
	 */
	function getRandomInt(min, max) {
	    return Math.floor(Math.random() * (max - min + 1)) + min;
	}
	/**
	 | @var string
	 */
	var SESSID = (new Date()).toISOString().substring(0, 10);// + Date.now();
	/**
	 | @var string
	 */
	var player1 = 'bot2';
	/**
	 | @var string
	 */
	var player2 = 'bot1';

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
		// type = (Board.TYPE_P == type) ? type : Board.TYPE_L;
		this.cols = 20;
		this.rows = 8;
	};
	Board.prototype = {
		/**
		 | 
		 */
		shootCnt: 0,
		/**
		 | Init board (render board with cols and rows)
		 */
		_$board: null,
		/**
		 | Init board (render board with cols and rows)
		 */
		init: function render(container) {
			var html = '<table class="table table-striped table-bordered table-condensed" data->';
			for (var row = 0; row < this.rows; row++) {
				html += '<tr class="row' + row + '">';
				for (var col = 0; col < this.cols; col++) {
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
			var shipName = '', shipNames = {}, shipCnt = 0;
			for (var ship of (ships || [])) {
				var vector = []; var num = 0;
				for (var row = 0; row < ship.matrix.length; row++) {
					vector = ship.matrix[row];
					for (var col = 0; col < vector.length; col++) {
						num = vector[col];
						if (!num) { continue; }
						var slt = 'tr.row' + (ship.coordinates[1] + row) + ' > td.col' + (ship.coordinates[0] + col);
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
						shipName = $.trim(ship.name || ship.type);
						$td.addClass(shipName);
						shipName += ('0' + shipCnt).slice(-2);
						$td.attr('data-ship', shipName);
						shipNames[shipName] = 1;
						// console.log('slt: ', slt, ' - $td: ',  $td.get(0));
					}
				}
				shipCnt++;
			}
			console.log('shipNames: ', shipNames);
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
		notify: function notify(dataArr, cb) {
			var notifyData = {
				playerId: player1,
				shots: [],
				sunkShips: []
			};
			for (var data of dataArr) {
				var shot = {
					coordinate: data,
					status: 'MISS'
				};
				var sunkShip = null;
				var $td = this._styleTdShoot(data[0], data[1]);
				if (!($td && $td.length)) {
					alert('Shoot failed: ' + JSON.stringify(data));
					return;
				}
				var ship = $.trim($td.attr('data-ship'));
				if (ship) { // Hit yes
					shot['status'] = 'HIT';
					var sltShip = 'td[data-ship="' + ship + '"]';
					var sltShoot = 'td[data-shoot]';
					var $tdShips = this._$board.find(sltShip);
					var $tdShoots = $tdShips.filter(sltShoot);
					// console.log(sltShip, sltShoot, $tdShips, $tdShoots);
					// Case: ship down! 
					if ($tdShips.length == $tdShoots.length) {
						var coordinates = [];
						$tdShips.each(function(){
							var $this = $(this), $tr = $this.parent();
							var col = (1 * ($this.attr('class').match(/col(\d+)/) || [])[1] || '0');
							var row = (1 * ($tr.attr('class').match(/row(\d+)/) || [])[1] || '0')
							coordinates.push([col, row]);
						});
						notifyData.sunkShips.push({
							'type': ship.slice(0, -2),
							'coordinates': coordinates
						});
					}
					
				}
				//
				notifyData.shots.push(shot);
				// Game end?
				$tdShips = this._$board.find('td[data-ship]');
				$tdShoots = $tdShips.filter('td[data-shoot]');
				if ($tdShips.length == $tdShoots.length) {
					// Fire callback
					(cb || $.noop)();
					//
					this._$board.attr('data-game_end', 1);
					setTimeout(function(){
						alert('Game over!!!');
					}, 256);
				}
				console.log('shoot#' + Battleship.shootCnt + ' data: ', (data[1] + ':' + data[0]), ' - notifyData: ', notifyData);
			}
			return notifyData;
		},
		/**
		 | 
		 */
		_styleTdShoot: function notify(x, y) {
			var slt = 'tr.row' + y + ' > td.col' + x;
			var $td = this._$board.find(slt);
			if ($td && $td.length) {
				var dataShoot = (1 * ($td.attr('data-shoot') || 0)) + 1;
				$td.attr('data-shoot', dataShoot);
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
			// data = $.extend({ sessionid: SESSID }, data);
			// @param RestClient
			var request = new $.RestClient('/', {
				// http://api.jquery.com/jQuery.ajax/
				ajax: {
					headers: {'X-SESSION-ID': SESSID, 'X-TOKEN': Date.now()}
				} 
			}).add(type).create(data);
			request.done(function(result/* , textStatus, xhrObject */){
				// Case: error
				if (result && result.error) {
					cb(new Error(result.error));
				} else {
					cb(null, result);
				}
			});
		},
		/**
		 | invite
		 */
		invite: function invite(data, cb) {
			this.call('invite', $.extend(data, {}), cb);
		},
		/**
		 | place ships
		 */
		placeShips: function placeShips(data, cb) {
			this.call('place-ships', $.extend(data, {}), cb);
		},
		/**
		 | 
		 */
		shoot: function shoot(data, cb) {
			this.call('shoot', $.extend(data, {}), cb);
		}, 
		/**
		 | 
		 */
		notify: function notify(data, isHit) {
			data = $.extend({ 'is_hit': isHit }, data); 
			this.call('notify', data);
		}, 
		/**
		 | 
		 */
		gameOver: function gameOver(data, cb) {
			this.call('game-over', $.extend(data, {}), cb);
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
		 | @var number Number of shoots
		 */
		shootCnt: 0,
		
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
			var ships = [];
			var types = ['CV', 'BB', 'OR'/*, 'CA', 'DD'*/];
			for (var type of types) {
				ships.push({ type: type, quantity: getRandomInt(1, 1) });
			}
			//
			bot.invite({
				boardWidth: 20,
				boardHeight: 8,
				ships: ships
			}, function(err, data){
				console.log('bot invite done: ', err, data);
				// OK?
				if (!err) {
					// Place ships
					return bot.placeShips({
						player1: player1,
						player2: player2
					}, function(err, data){
						if (!err && data) {
							board.renderShips(data.ships);
							board.renderShoots(data.shoots);	
						}
						// Fire callback?
						cb(err, data);
					});
				}
				// Fire callback?
				cb(err, data);
			});
		},
		
		/**
		 | 
		 */
		shoot: function shoot(cb) {
			// Format cb
			cb = cb || $.noop;
			var self = this;
			this._bot.shoot({
				turn: ++this.shootCnt,
				maxShots: 1
			}, 
			function(err, data){
				if (err) {
					return alert(err);
				}
				//
				var notifyData = self._board.notify(data, function(){
					// Game over
					var data = {
						winner: player1,
						loser: player2,
						statistics: {
							numberOfTurns: self.shootCnt,
							elapsedTime: 0
						}
					};
					self._bot.gameOver();
				});
				self._bot.notify(notifyData);
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