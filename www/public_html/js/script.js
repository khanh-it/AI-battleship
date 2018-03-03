/**
 |
 |
 */
(function($){
	/**
	 |
	 |
	 */
	var Board = Board || {
		// Num of cols
		COLS: 8,
		// Num of rows
		ROWS: 25,
		/**
		 | Init board (render board with cols and rows)
		 */
		_$board: null,
		/**
		 | Init board (render board with cols and rows)
		 */
		init: function render(container, options) {
			// Format options
			this._options = options = (typeof options == 'object' ? options : {});
			// #end
			var html = '<table class="table table-striped table-bordered table-condensed">';
			for (var row = 1; row <= this.ROWS; row++) {
				html += '<tr class="row' + row + '">';
				for (var col = 1; col <= this.COLS; col++) {
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
			$.post('/', { type: type, data: data }, cb, 'json');
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
		 | @var Bot
		 */
		_botAlpha: null,
		
		/**
		 | @var Bot
		 */
		_botBeta: null,
		
		/**
		 | 
		 */
		initGame: function initGame() {
			var botAlpha = this._botAlpha = new Bot();
			botAlpha.init(function(result){
				console.log('botAlpha init done: ', result);
				// OK?
				if (result && (1 * result.status)) {
					var data = result.data || {};
					// Render ships?
					Board.renderShips(data.ships)
				}
			});
			
			var botBeta = this._botBeta = new Bot();
			botBeta.init();
		}
	};

	// Render board
	$grids = $('#grids');
	Board.init($grids.get(0));

	// Start game?!
	$btnStartNewGame = $('#btn-start_new_game');
	$btnStartNewGame.click(function(){
		GE.initGame();	
	});
	setTimeout(function(){
		$btnStartNewGame.click();
	}, 512);
})(jQuery);