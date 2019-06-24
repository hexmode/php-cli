<?php

namespace splitbrain\phpcli;

use Throwable;

/**
 * Class Exception
 *
 * The code is used as exit code for the CLI tool. This should
 * probably be extended. Many cases just fall back to the E_ANY code.
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @license MIT
 */
class Exception extends \RuntimeException {
	// no error code specified
	const E_ANY = -1;
	// Unrecognized option
	const E_UNKNOWN_OPT = 1;
	// Option requires argument
	const E_OPT_ARG_REQUIRED = 2;
	// Option not allowed argument
	const E_OPT_ARG_DENIED = 3;
	// Option abiguous
	const E_OPT_ABIGUOUS = 4;
	// Could not read argv
	const E_ARG_READ = 5;

	/**
	 * @param string $message The Exception message to throw.
	 * @param int $code The Exception code
	 * @param \Exception|null $previous The previous exception used for
	 *   the exception chaining.
	 */
	public function __construct(
		string $message = "",
		int $code = 0,
		Throwable $previous = null
	) {
		if ( !$code ) {
			$code = self::E_ANY;
		}
		parent::__construct( $message, $code, $previous );
	}
}
