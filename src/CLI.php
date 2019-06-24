<?php

namespace splitbrain\phpcli;

use Throwable;

/**
 * Class CLI
 *
 * Your commandline script should inherit from this class and
 * implement the abstract methods.
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @license MIT
 */
abstract class CLI {
	/** @var  Options the option parser */
	protected $options;
	/** @var  Colors */
	public $colors;

	/** @var array PSR-3 compatible loglevels and their prefix, color,
	 * output channel */
	protected $loglevel = [
		'debug' => [ '', Colors::C_LIGHTGRAY, STDOUT ],
		'info' => [ 'ℹ ', Colors::C_CYAN, STDOUT ],
		'notice' => [ '☛ ', Colors::C_CYAN, STDOUT ],
		'success' => [ '✓ ', Colors::C_GREEN, STDOUT ],
		'warning' => [ '⚠ ', Colors::C_BROWN, STDERR ],
		'error' => [ '✗ ', Colors::C_RED, STDERR ],
		'critical' => [ '☠ ', Colors::C_LIGHTRED, STDERR ],
		'alert' => [ '✖ ', Colors::C_LIGHTRED, STDERR ],
		'emergency' => [ '✘ ', Colors::C_LIGHTRED, STDERR ],
	];

	/** @var string $logdefault log level */
	protected $logdefault = 'info';

	/**
	 * constructor
	 *
	 * Initialize the arguments, set up helper classes and set up the
	 * CLI environment
	 *
	 * @param bool $autocatch should exceptions be catched and handled
	 *   automatically?
	 * @param callable|null $postCmd command to run after the error is
	 *   shown.
	 */
	public function __construct(
		bool $autocatch = true,
		?callable $postCmd = null
	) {
		if ( $autocatch ) {
			$this->setAutocatch( $postCmd );
		}

		$this->colors = new Colors();
		$this->options = new Options( $this->colors );
	}

	/**
	 * Set up for automatic catches
	 *
	 * @param callable|null $postCmd
	 */
	public function setAutocatch( ?callable $postCmd = null ) :void {
		set_exception_handler(
			function ( Throwable $err ) use ( $postCmd ) {
				$this->fatalThrow( $err, $postCmd );
			}
		);
	}

	/**
	 * Register options and arguments on the given $options object
	 *
	 * @param Options $options
	 * @return void
	 *
	 * @throws Exception
	 */
	abstract protected function setup( Options $options );

	/**
	 * Your main program
	 *
	 * Arguments and options have been parsed when this is run
	 *
	 * @param Options $options
	 * @return void
	 *
	 * @throws Exception
	 */
	abstract protected function main( Options $options );

	/**
	 * Execute the CLI program
	 *
	 * Executes the setup() routine, adds default options, initiate
	 * the options parsing and argument checking and finally executes
	 * main() - Each part is split into their own protected function
	 * below, so behaviour can easily be overwritten
	 *
	 * @throws Exception
	 */
	public function run() :void {
		if ( 'cli' != PHP_SAPI ) {
			throw new Exception(
				'This has to be run from the command line'
			);
		}

		$this->setup( $this->options );
		$this->registerDefaultOptions();
		$this->parseOptions();
		$this->handleDefaultOptions();
		$this->setupLogging();
		$this->checkArgments();
		$this->execute();

		exit( 0 );
	}

	// region run handlers - for easier overriding

	/**
	 * Add the default help, color and log options
	 */
	protected function registerDefaultOptions() :void {
		$this->options->registerOption(
			'help', 'Display this help screen and exit immediately.', 'h'
		);
		$this->options->registerOption(
			'no-colors', 'Do not use any colors in output. Useful when '
			. 'pipingoutput to other tools or files.'
		);
		$this->options->registerOption(
			'loglevel',
			'Minimum level of messages to display. Default is '
			. $this->colors->wrap( $this->logdefault, Colors::C_CYAN )
			. '. Valid levels are: debug, info, notice, success, warning, '
			. 'error, critical, alert, emergency.', null, 'level'
		);
	}

	/**
	 * Handle the default options
	 */
	protected function handleDefaultOptions() :void {
		if ( $this->options->getOpt( 'no-colors' ) ) {
			$this->colors->disable();
		}
		if ( $this->options->getOpt( 'help' ) ) {
			echo $this->options->help();
			exit( 0 );
		}
	}

	/**
	 * Handle the logging options
	 */
	protected function setupLogging() :void {
		$level = $this->options->getOpt( 'loglevel', $this->logdefault );
		if ( !isset( $this->loglevel[$level] ) ) {
			$this->fatal( 'Unknown log level' );
		}
		foreach ( array_keys( $this->loglevel ) as $l ) {
			if ( $l == $level ) {
				break;
			}
			unset( $this->loglevel[$l] );
		}
	}

	/**
	 * Wrapper around the option parsing
	 */
	protected function parseOptions() :void {
		$this->options->parseOptions();
	}

	/**
	 * Wrapper around the argument checking
	 */
	protected function checkArgments() :void {
		$this->options->checkArguments();
	}

	/**
	 * Wrapper around main
	 */
	protected function execute() :void {
		$this->main( $this->options );
	}

	// endregion

	// region logging

	/**
	 * Handler for thrown objects
	 *
	 * @param Throwable $error
	 * @param callable|null $postCmd
	 */
	public function fatalThrow(
		Throwable $error,
		?callable $postCmd = null
	) :void {
		$this->debug(
			get_class( $error ) . ' caught in ' . $error->getFile() . ':'
			. $error->getLine()
		);
		$this->debug( $error->getTraceAsString() );

		if (
			$postCmd === null
			&& is_a( $error, __NAMESPACE__ . '\UsageException', true )
		) {
			$postCmd = function () :void {
				echo $this->options->help();
			};
		}

		// https://github.com/vimeo/psalm/issues/1148 for the cast to int
		$this->fatal(
			$error->getMessage(), [], (int)$error->getCode(), $postCmd
		);
	}

	/**
	 * Exits the program on a fatal error
	 *
	 * @param string $error either an exception or an error message
	 * @param array $context
	 * @param int $code error code to exit with
	 * @param callable|null $postCmd do this just before exiting
	 */
	public function fatal(
		string $error,
		array $context = [],
		int $code = Exception::E_ANY,
		?callable $postCmd = null
	) :void {
		$this->critical( $error, $context );
		if ( is_callable( $postCmd ) ) {
			call_user_func( $postCmd );
		}
		exit( $code );
	}

	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 *
	 * @return void
	 */
	public function emergency( $message, array $context = [] ) :void {
		$this->log( 'emergency', $message, $context );
	}

	/**
	 * Action must be taken immediately.
	 *
	 * Example: Entire website down, database unavailable, etc. This should
	 * trigger the SMS alerts and wake you up.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function alert( $message, array $context = [] ) :void {
		$this->log( 'alert', $message, $context );
	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function critical( $message, array $context = [] ) :void {
		$this->log( 'critical', $message, $context );
	}

	/**
	 * Runtime errors that do not require immediate action but should
	 * typically be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function error( $message, array $context = [] ) :void {
		$this->log( 'error', $message, $context );
	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable
	 * things that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function warning( $message, array $context = [] ) :void {
		$this->log( 'warning', $message, $context );
	}

	/**
	 * Normal, positive outcome
	 *
	 * @param string $string
	 * @param array $context
	 */
	public function success( $string, array $context = [] ) :void {
		$this->log( 'success', $string, $context );
	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function notice( $message, array $context = [] ) :void {
		$this->log( 'notice', $message, $context );
	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function info( $message, array $context = [] ) :void {
		$this->log( 'info', $message, $context );
	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 */
	public function debug( $message, array $context = [] ) :void {
		$this->log( 'debug', $message, $context );
	}

	/**
	 * @param string $level
	 * @param string $message
	 * @param array $context
	 */
	public function log( $level, $message, array $context = [] ) :void {
		// is this log level wanted?
		if ( !isset( $this->loglevel[$level] ) ) {
			return;
		}

		/** @var string $prefix */
		/** @var string $color */
		/** @var resource $channel */
		list( $prefix, $color, $channel ) = $this->loglevel[$level];
		if ( !$this->colors->isEnabled() ) { $prefix = '';
		}

		$message = $this->interpolate( $message, $context );
		$this->colors->ptln( $prefix . $message, $color, $channel );
	}

	/**
	 * Interpolates context values into the message placeholders.
	 *
	 * @param string $message
	 * @param array $context
	 * @return string
	 */
	public function interpolate(
		string $message,
		array $context = []
	) :string {
		// build a replacement array with braces around the context keys
		$replace = [];
		foreach ( $context as $key => $val ) {
			// check that the value can be casted to string
			if (
				!is_array( $val ) && (
					 !is_object( $val ) ||
					 method_exists( $val, '__toString' )
				)
			) {
				$replace['{' . $key . '}'] = $val;
			}
		}

		// interpolate replacement values into the message and return
		return strtr( $message, $replace );
	}

	// endregion
}
