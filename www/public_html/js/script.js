/**
 |
 |
 */
(function($){
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
		type = (Board.TYPE_L == type) ? type : Board.TYPE_P;
		if (Board.TYPE_P == type) {
			this.cols = 8;
			this.rows = 25;
		} else {
			this.cols = 25;
			this.rows = 8;
		}
	};
	Board.prototype = {
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
					html += 	'<span>&nbsp;</span>';
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
			var ship = null;
			for (var i in (ships || [])) {
				this.renderShip(ships[i]);
			}
		},
		/**
		 | Render ship
		 | @param object ship
		 */
		renderShip: function renderShip(ship) {
			if (ship) {
				for (var col = 0; col < ship.cols; col++) {
					for (var row = 0; row < ship.rows; row++) {
						var slt = 'tr.row' + (ship.y + row) + ' > td.col' + (ship.x + col);
						var $td = this._$board.find(slt);
						$td.attr('data-ship', (ship.name || ship.type));
						console.log('slt: ', slt, ' - $td: ',  $td.get(0));
					}
				}
			}
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
		// +++
		this.name = options.name || ('Bot_' + (Math.random() + Date.now()));
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
			$.post('/', { type: type, data: data }, function(){
				if (result && (1 * result.status)) {
					cb(null, result.data);
				} else {
					cb(new Error(result.msg));
				}
			}, 'json');
		},
		
		/**
		 | 
		 */
		init: function init(cb) {
			// 
			this.call('start_new_game', {
				name: this.name
			}, cb);
		}
	};

	/**
	 | Game Engine simulator
	 |
	 */
	var GE = GE || {
		/**
		 | @var Board
		 */
		_board: null,

		/**
		 | @var Bot
		 */
		_botAlpha: null,
		
		/**
		 | @var Bot
		 */
		_botBeta: null,

		requestShoot: function requestShoot() {
			this._botBeta.shoot();
		},
		
		/**
		 | 
		 */
		initGame: function initGame(container, cb) {
			// Format cb
			cb = cb || $.noop;
			// Init, + render board
			var board = this._board = new Board();
			board.init(container);
			// #end

			var botAlpha = this._botAlpha = new Bot();
			var botBeta = this._botBeta = new Bot();
			//
			botAlpha.init(function(err, data){
				console.log('botAlpha init done: ', err, data);
				// OK?
				if (!err) {
					// Render ships?
					board.renderShips(data.ships)
					// 
					botBeta.init(function(err, data){
						console.log('botBeta init done: ', err, data);
						// OK?
						if (!err) {
							cb(); // Fire callback
						}
					});
				}
			});
		}
	};

	// Start game?!
	var $grids = $('#grids');
	var $btnStartNewGame = $('#btn-start_new_game');
	var $btnShoot = $('#btn-shoot');

	$btnStartNewGame.click(function(){
		GE.initGame($grids.get(0), function(){
			//
			$btnShoot.off('click').on('click', function(evt){
				GE.requestShoot();
			});
			// #end
		});
	});
	setTimeout(function(){
		$btnStartNewGame.click();
	}, 368);
})(jQuery);