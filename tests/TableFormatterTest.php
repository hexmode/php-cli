<?php

namespace splitbrain\phpcli\tests;

use splitbrain\phpcli\Colors;

class TableFormatter extends \splitbrain\phpcli\TableFormatter {
	public function calculateColLengths( $columns ) :array {
		return parent::calculateColLengths( $columns );
	}

	public function strlen( string $string ) :int {
		return parent::strlen( $string );
	}

	public function wordwrap(
		string $str,
		int $width = 75,
		string $break = "\n",
		bool $cut = false
	) :string {
		return parent::wordwrap( $str, $width, $break, $cut );
	}

}

class TableFormatterTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Provide test data for column width calculations
	 *
	 * @return array
	 */
	public function calcProvider() {
		return [
			[
				[ 5, 5, 5 ],
				[ 5, 5, 88 ]
			],

			[
				[ '*', 5, 5 ],
				[ 88, 5, 5 ]
			],

			[
				[ 5, '50%', '50%' ],
				[ 5, 46, 47 ]
			],

			[
				[ 5, '*', '50%' ],
				[ 5, 47, 46 ]
			],
		];
	}

	/**
	 * Test calculation of column sizes
	 *
	 * @dataProvider calcProvider
	 * @param array $input
	 * @param array $expect
	 * @param int $max
	 * @param string $border
	 */
	public function test_calc(
		$input, $expect, $max = 100, $border = ' '
	) :void {
		$tf = new TableFormatter();
		$tf->setMaxWidth( $max );
		$tf->setBorder( $border );

		$result = $tf->calculateColLengths( $input );

		$this->assertEquals(
			$max, array_sum( $result ) +
			( strlen( $border ) * ( count( $input ) - 1 ) )
		);
		$this->assertEquals( $expect, $result );
	}

	/**
	 * Check wrapping
	 */
	public function test_wrap() :void {
		$text = "this is a long string something\n" .
			"123456789012345678901234567890";

		$expt = "this is a long\n" .
			"string\n" .
			"something\n" .
			"123456789012345\n" .
			"678901234567890";

		$tf = new TableFormatter();
		$this->assertEquals( $expt, $tf->wordwrap( $text, 15, "\n", true ) );
	}

	public function test_length() :void {
		$text = "this is häppy ☺";
		$expect = "$text     |test";

		$tf = new TableFormatter();
		$tf->setBorder( '|' );
		$result = $tf->format( [ 20, '*' ], [ $text, 'test' ] );

		$this->assertEquals( $expect, trim( $result ) );
	}

	public function test_colorlength() :void {
		$color = new Colors();

		$text = 'this is ' . $color->wrap( 'green', Colors::C_GREEN );
		$expect = "$text       |test";

		$tf = new TableFormatter();
		$tf->setBorder( '|' );
		$result = $tf->format( [ 20, '*' ], [ $text, 'test' ] );

		$this->assertEquals( $expect, trim( $result ) );
	}

	public function test_onewrap() :void {
		$col1 = "test\nwrap";
		$col2 = "test";

		$expect = "test |test \n" .
			"wrap |     \n";

		$tf = new TableFormatter();
		$tf->setMaxWidth( 11 );
		$tf->setBorder( '|' );

		$result = $tf->format( [ 5, '*' ], [ $col1, $col2 ] );
		$this->assertEquals( $expect, $result );
	}
}
