<?php
$_SERVER['HTTP_HOST'] = 'phpunit_fake_http_host';
require_once (__DIR__ . '/../../core/lib/Clementine.php');
// fix for code coverage: http://www.voidcn.com/blog/Tom_Green/article/p-6004162.html
$php_token_autoload_file = '/usr/share/php/PHP/Token/Stream/Autoload.php';
if (file_exists($php_token_autoload_file)) {
    require_once($php_token_autoload_file);
}
global $Clementine;
$Clementine = new Clementine();
$Clementine->run(true);

ini_set('log_errors', 'off');
ini_set('display_errors', 'on');

class ormOrmTest extends PHPUnit_Framework_TestCase
{

    public function testsanitizeBoolean()
    {
        global $Clementine;
        $ormModel = $Clementine->getModel('orm');
        $this->assertTrue($ormModel->sanitizeBoolean() === 0);
        $this->assertTrue($ormModel->sanitizeBoolean(true) === 1);
        $this->assertTrue($ormModel->sanitizeBoolean(0) === 0);
        $this->assertTrue($ormModel->sanitizeBoolean('00') === 1);
        $this->assertTrue($ormModel->sanitizeBoolean(false) === 0);
        $this->assertTrue($ormModel->sanitizeBoolean(1) === 1);
        $this->assertTrue($ormModel->sanitizeBoolean('') === 0);
        $this->assertTrue($ormModel->sanitizeBoolean(null) === 0);
        $this->assertTrue($ormModel->sanitizeBoolean(array()) === 0);
        $this->assertTrue($ormModel->sanitizeBoolean(array(
            '0'
        )) === array(
            0
        ));
        $this->assertTrue($ormModel->sanitizeBoolean(array(
            '1'
        )) === array(
            1
        ));
        $this->assertTrue($ormModel->sanitizeBoolean(array(
            null,
            '',
            '00',
            1.5,
            '1.5',
            '1,5',
            'notnull',
            '0notnull',
            '1notnull',
            '1',
            '<script></script>',
            '<script>0</script>',
            '<script>1</script>'
        )) === array(
            0,
            0,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1,
            1
        ));
    }

    public function testsanitizeInt()
    {
        global $Clementine;
        $ormModel = $Clementine->getModel('orm');
        $this->assertTrue($ormModel->sanitizeInt() === 0);
        $this->assertTrue($ormModel->sanitizeInt(true) === 1);
        $this->assertTrue($ormModel->sanitizeInt(0) === 0);
        $this->assertTrue($ormModel->sanitizeInt('00') === 0);
        $this->assertTrue($ormModel->sanitizeInt(false) === 0);
        $this->assertTrue($ormModel->sanitizeInt(1) === 1);
        $this->assertTrue($ormModel->sanitizeInt(13) === 13);
        $this->assertTrue($ormModel->sanitizeInt('') === 0);
        $this->assertTrue($ormModel->sanitizeInt(null) === 0);
        $this->assertTrue($ormModel->sanitizeInt(array()) === 0);
        $this->assertTrue($ormModel->sanitizeInt(array(
            '0'
        )) === array(
            0
        ));
        $this->assertTrue($ormModel->sanitizeInt(array(
            '1'
        )) === array(
            1
        ));
        $this->assertTrue($ormModel->sanitizeInt(array(
            null,
            '',
            '00',
            1.5,
            '1.5',
            '1,5',
            'notnull',
            '0notnull',
            '1notnull',
            '1',
            '<script></script>',
            '<script>0</script>',
            '<script>1</script>'
        )) === array(
            0,
            0,
            0,
            1,
            1,
            1,
            0,
            0,
            1,
            1,
            0,
            0,
            0
        ));
    }

    public function testsanitizeFloat()
    {
        global $Clementine;
        $ormModel = $Clementine->getModel('orm');
        $this->assertTrue($ormModel->sanitizeFloat() === 0.0);
        $this->assertTrue($ormModel->sanitizeFloat(true) === 1.0);
        $this->assertTrue($ormModel->sanitizeFloat(0) === 0.0);
        $this->assertTrue($ormModel->sanitizeFloat('00') === 0.0);
        $this->assertTrue($ormModel->sanitizeFloat(false) === 0.0);
        $this->assertTrue($ormModel->sanitizeFloat(1) === 1.0);
        $this->assertTrue($ormModel->sanitizeFloat(13) === 13.0);
        $this->assertTrue($ormModel->sanitizeFloat('') === 0.0);
        $this->assertTrue($ormModel->sanitizeFloat(null) === 0.0);
        $this->assertTrue($ormModel->sanitizeFloat(array()) === 0.0);
        $this->assertTrue($ormModel->sanitizeFloat(array(
            '0'
        )) === array(
            0.0
        ));
        $this->assertTrue($ormModel->sanitizeFloat(array(
            '1'
        )) === array(
            1.0
        ));
        $this->assertTrue($ormModel->sanitizeFloat(array(
            null,
            '',
            '00',
            1.5,
            '1.5',
            '1,5',
            'notnull',
            '0notnull',
            '1notnull',
            '1',
            '<script></script>',
            '<script>0</script>',
            '<script>1</script>'
        )) === array(
            0.0,
            0.0,
            0.0,
            1.5,
            1.5,
            1.0,
            0.0,
            0.0,
            1.0,
            1.0,
            0.0,
            0.0,
            0.0
        ));
        $this->assertTrue($ormModel->sanitizeFloat(0.4) === 0.4);
        $this->assertTrue($ormModel->sanitizeFloat('0.4') === 0.4);
        $this->assertTrue($ormModel->sanitizeFloat('0,4') === 0.0);
        $this->assertTrue($ormModel->sanitizeFloat(1.6) === 1.6);
        $this->assertTrue($ormModel->sanitizeFloat(13.0) === 13.0);
        $this->assertTrue($ormModel->sanitizeFloat(array(
            '0.4'
        )) === array(
            0.4
        ));
        $this->assertTrue($ormModel->sanitizeFloat(array(
            1.4
        )) === array(
            1.4
        ));
        $this->assertTrue($ormModel->sanitizeFloat(array(
            '1.4'
        )) === array(
            1.4
        ));
        $this->assertTrue($ormModel->sanitizeFloat(array(
            null,
            '',
            '00',
            1.5,
            '1.5',
            '1,5',
            'notnull',
            '0notnull',
            '1notnull',
            '1',
            '<script></script>',
            '<script>0</script>',
            '<script>1</script>',
            '0.4notnull',
            '1.4notnull',
            '<script>0.4</script>',
            '<script>1.4</script>'
        )) === array(
            0.0,
            0.0,
            0.0,
            1.5,
            1.5,
            1.0,
            0.0,
            0.0,
            1.0,
            1.0,
            0.0,
            0.0,
            0.0,
            0.4,
            1.4,
            0.0,
            0.0
        ));
    }

    public function testsanitizeString()
    {
        global $Clementine;
        $ormModel = $Clementine->getModel('orm');
        $this->assertTrue($ormModel->sanitizeString() === '');
        $this->assertTrue($ormModel->sanitizeString(true) === '1');
        $this->assertTrue($ormModel->sanitizeString(0) === '0');
        $this->assertTrue($ormModel->sanitizeString('00') === '00');
        $this->assertTrue($ormModel->sanitizeString(false) === '');
        $this->assertTrue($ormModel->sanitizeString(1) === '1');
        $this->assertTrue($ormModel->sanitizeString(13) === '13');
        $this->assertTrue($ormModel->sanitizeString(09) === '0');
        $this->assertTrue($ormModel->sanitizeString('09') === '09');
        $this->assertTrue($ormModel->sanitizeString('') === '');
        $this->assertTrue($ormModel->sanitizeString(null) === '');
        $this->assertTrue($ormModel->sanitizeString(array()) === '');
        $this->assertTrue($ormModel->sanitizeString(array(
            '0'
        )) === array(
            '0'
        ));
        $this->assertTrue($ormModel->sanitizeString(array(
            '1'
        )) === array(
            '1'
        ));
        $this->assertTrue($ormModel->sanitizeString(array(
            null,
            '',
            '00',
            1.5,
            '1.5',
            '1,5',
            'notnull',
            '0notnull',
            '1notnull',
            '1',
            '<script></script>',
            '<script>0</script>',
            '<script>1</script>',
            '<b>titre</b>',
            '<a><b class="test"></a>titre</b>',
        )) === array(
            '',
            '',
            '00',
            '1.5',
            '1.5',
            '1,5',
            'notnull',
            '0notnull',
            '1notnull',
            '1',
            '',
            '0',
            '1',
            'titre',
            'titre',
        ));
    }

    public function testsanitizeValues()
    {
        global $Clementine;
        $ormModel = $Clementine->getModel('orm');
        // set field types
        $ormModel->fields = array(
            'table.varchar1' => array('type' => 'varchar'),
            'table.varchar2' => array('type' => 'varchar'),
            'table.varchar3' => array('type' => 'varchar'),
            'table.varchar4' => array('type' => 'varchar'),
            'table.varchar5' => array('type' => 'varchar'),
            'table.varchar6' => array('type' => 'varchar'),
            'table.int1' => array('type' => 'int'),
            'table.int2' => array('type' => 'int'),
            'table.int3' => array('type' => 'int'),
            'table.int4' => array('type' => 'int'),
            'table.float1' => array('type' => 'float'),
            'table.float2' => array('type' => 'float'),
            'table.float3' => array('type' => 'float'),
            'table.float4' => array('type' => 'float'),
            'table.boolean1' => array('type' => 'boolean'), // not a php boolean, 0 or 1
            'table.boolean2' => array('type' => 'boolean'), // not a php boolean, 0 or 1
            'table.boolean3' => array('type' => 'boolean'), // not a php boolean, 0 or 1
            'table.boolean4' => array('type' => 'boolean'), // not a php boolean, 0 or 1
        );
        // test
        $this->assertTrue($ormModel->sanitizeValues() === array());
        $this->assertTrue($ormModel->sanitizeValues(null) === array());
        $this->assertTrue($ormModel->sanitizeValues('') === array());
        $this->assertTrue($ormModel->sanitizeValues(0) === array());
        $this->assertTrue($ormModel->sanitizeValues(array()) === array());
        $this->assertTrue($ormModel->sanitizeValues(array(
            0 => '1',
            1 => '2',
            2 => 'this is a string',
            3 => 'this is <br />some html',
            'table.varchar1' => null,
            'table.varchar2' => '',
            'table.varchar3' => 1,
            'table.varchar4' => '02',
            'table.varchar5' => 'this is <strong>some</strong> html',
            'table.varchar6' => array(
                1,
                'this is <strong>some</strong> html',
            ),
            'table.int1' => null,
            'table.int2' => '',
            'table.int3' => '30test',
            'table.int4' => array(
                '10',
                '30test',
            ),
            'table.float1' => null,
            'table.float2' => '',
            'table.float3' => '30.2 toto',
            'table.float4' => '30,2',
            'table.boolean1' => null,
            'table.boolean2' => '',
            'table.boolean3' => 2,
            'table.boolean4' => '2test',
        )) === array(
            0 => '1',
            1 => '2',
            2 => 'this is a string',
            3 => 'this is some html',
            'table.varchar1' => '',
            'table.varchar2' => '',
            'table.varchar3' => '1',
            'table.varchar4' => '02',
            'table.varchar5' => 'this is some html',
            'table.varchar6' => array(
                '1',
                'this is some html',
            ),
            'table.int1' => 0,
            'table.int2' => 0,
            'table.int3' => 30,
            'table.int4' => array(
                10,
                30,
            ),
            'table.float1' => 0.0,
            'table.float2' => 0.0,
            'table.float3' => 30.2,
            'table.float4' => 30.0,
            'table.boolean1' => 0,
            'table.boolean2' => 0,
            'table.boolean3' => 1,
            'table.boolean4' => 1,
        ));
    }

}
