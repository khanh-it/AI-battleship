/**
 |
 |
 */
(function($){
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
	}
	Bot.prototype = {
		/**
		 | @param Bot
		 */
		call: function call(type, data, cb) {
			$.post('/', { type: type, data: data }, cb);
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
			botAlpha.init();
			
			
			var botBeta = this._botBeta = new Bot();
			botBeta.init();
		}
	}

	// Start game?!
	$btnStartNewGame = $('#btn-start_new_game');
	$btnStartNewGame.click(function(){
		GE.initGame();	
	});
})(jQuery);