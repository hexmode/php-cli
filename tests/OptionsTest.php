<?php

namespace splitbrain\phpcli\tests;

class Options extends \splitbrain\phpcli\Options {

	public $args;
}

class OptionsTest extends \PHPUnit\Framework\TestCase {

	function test_simpleshort() :void {
		$options = new Options();
		$options->registerOption( 'exclude', 'exclude files', 'x', 'file' );

		$options->args = [ '-x', 'foo', 'bang' ];
		$options->parseOptions();

		$this->assertEquals( 'foo', $options->getOpt( 'exclude' ) );
		$this->assertEquals( [ 'bang' ], $options->args );
		$this->assertFalse( $options->getOpt( 'nothing' ) );
	}

	function test_simplelong1() :void {
		$options = new Options();
		$options->registerOption( 'exclude', 'exclude files', 'x', 'file' );

		$options->args = [ '--exclude', 'foo', 'bang' ];
		$options->parseOptions();

		$this->assertEquals( 'foo', $options->getOpt( 'exclude' ) );
		$this->assertEquals( [ 'bang' ], $options->args );
		$this->assertFalse( $options->getOpt( 'nothing' ) );
	}

	function test_simplelong2() :void {
		$options = new Options();
		$options->registerOption( 'exclude', 'exclude files', 'x', 'file' );

		$options->args = [ '--exclude=foo', 'bang' ];
		$options->parseOptions();

		$this->assertEquals( 'foo', $options->getOpt( 'exclude' ) );
		$this->assertEquals( [ 'bang' ], $options->args );
		$this->assertFalse( $options->getOpt( 'nothing' ) );
	}

	function test_complex() :void {
		$options = new Options();

		$options->registerOption( 'plugins', 'run on plugins only', 'p' );
		$options->registerCommand( 'status', 'display status info' );
		$options->registerOption(
			'long', 'display long lines', 'l', false, 'status'
		);

		$options->args = [ '-p', 'status', '--long', 'foo' ];
		$options->parseOptions();

		$this->assertEquals( 'status', $options->getCmd() );
		$this->assertTrue( $options->getOpt( 'plugins' ) );
		$this->assertTrue( $options->getOpt( 'long' ) );
		$this->assertEquals( [ 'foo' ], $options->args );
	}
}
