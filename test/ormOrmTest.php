<?php
class ormOrmTest extends ormOrmTest_Parent
{

    public static function setUpBeforeClass()
    {
        // si cette classe n'a pas été surchargée on ne fait rien
        if (get_parent_class(get_parent_class(get_called_class())) == __CLASS__) {
            $color = "\033" . Clementine::$config['clementine_shell_colors']['warn'];
            $normal = "\033" . Clementine::$config['clementine_shell_colors']['normal'];
            fwrite(STDERR, $color . 'ORM tests wont run unless you override ' . __CLASS__ . $normal . PHP_EOL);
            return false;
        }
        static::setUpTestDB();
        return parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass()
    {
        // si cette classe n'a pas été surchargée on ne fait rien
        if (get_parent_class(get_parent_class(get_called_class())) == __CLASS__) {
            return false;
        }
        return parent::tearDownAfterClass();
    }

    public function setUp()
    {
        $this->test_uniqid = uniqid();
        $called_class = get_called_class();
        $reflection = new ReflectionClass($called_class);
        if (!$reflection->isFinal()) {
            $this->markTestSkipped($called_class . ' is not a final test class');
        }
    }

    public function tearDown()
    {
    }

    // name: nom du model crud à tester
    // primary_key_fields: tableau associatif permettant de définir les champs qui servent de clé primaire, avec valeurs éventuellement vides, sous la forme 'table-champ' => ''
    // fake_object: tableau associatif représentant l'élément crud, sous la forme 'table-champ' => ''
    // fake_updates: tableau associatif représentant les champs à mettre à jour, sous la forme 'table-champ' => 'valeur'. si valeur est un couple [ 'insecure' => 'bla<script>bla', 'secure' => 'blabla' ] on fournit 'insecure' lors de l'update et on s'assure que la valeur lue après update est bien 'secure'. cela permet de tester les fonctions sanitize
    // nb_tries: nombre de fois qu'on effectue successivement chaque test pour détecter les effets de bord : create, read, update, delete, sanitizes...
    public function testCrudModel($name = '', $primary_key_fields = array(), $fake_object = array(), $fake_updates = array(), $nb_tries = 3)
    {
        if (empty($name)) {
            return false;
        }
        global $Clementine;
        $this->testCrud_crud = $Clementine->getModel($name);
        //DONE: tester les effets de bord en bouclant sur 3 éléments
        //DONE: tester que le sanitize par fonction custom fonctionne bien avec la réinitialisation de sanitized_fields
        //DONE: tester que la sanitize par surcharge de sanitizeValues fonctionne bien avec la réinitialisation de sanitized_fields : cas du sanitize before et after
        //DONE: grosse fonction testCrudModel à splitter en sous-fonctions crudCreateModel, crudReadModel, crudUpdateModel (qu'on peut réutiliser en boucle pour tester les différents sanitize), crudDeleteModel, crudSanitizeDefault (qui appellera crudUpdateModel), crudSanitizeCustom, crudSanitizeNoCustom, crudSanitizeOverride...
        //DONE: créer une fonction générique testCrudModel (dans app/share/crud/test/crudCrudTest.php ?) qui prenne en paramètre le nom du crud à utiliser ('ocrfont') et un tableau représentant l'objet crud ($font_data)
        //TODO: permettre la création de crud sur plusieurs tables, avec clé étrangère et tests d'update avant delete bien sûr
        $this->testCrud_name = $name;
        $this->testCrud_primary_key_fields = $primary_key_fields;
        $this->testCrud_fake_object = $fake_object;
        $this->testCrud_updated_objects = array();
        for ($i = 0; $i < $nb_tries; ++$i) {
            $this->testCrud_updated_objects[$i] = $fake_object;
        }
        $this->testCrud_nb_tries = $nb_tries;
        $this->testCrud_test_uniqid = uniqid();
        $this->testCrud_last_insert_ids = $this->crudCreateModel();
        $this->testCrud_crud_primary_keys = $this->crudReadModel();
        $this->crudUpdateModel($fake_updates);
        $this->crudDeleteModel($this->testCrud_crud_primary_keys);
    }

    public function crudCreateModel()
    {
        $last_insert_ids = array();
        // create $nb_tries elements
        for ($i = 0; $i < $this->testCrud_nb_tries; ++$i) {
            $fake_object_i = $this->testCrud_fake_object;
            foreach ($fake_object_i as $field => &$val) {
                if (is_string($val)) {
                    $val = str_replace('$i', $i, $val);
                    $val = str_replace('$test_uniqid', $this->testCrud_test_uniqid, $val);
                }
            }
            $last_insert_ids_list = $this->testCrud_crud->create($fake_object_i);
            $this->assertTrue(is_array($last_insert_ids_list));
            foreach ($this->testCrud_primary_key_fields as $primary_key_tablefield => $pkval) {
                list($pktable, $pkfield) = explode('-', $primary_key_tablefield, 2);
                $this->assertTrue(!empty($pktable));
                $this->assertTrue(!empty($pkfield));
                if (empty($last_insert_ids_list[$pktable])) {
                    $last_insert_ids_list[$pktable] = array();
                }
                if (empty($last_insert_ids_list[$pktable][$pkfield])) {
                    $last_insert_ids_list[$pktable][$pkfield] = $pkval;
                }
            }
            $last_insert_ids[$i] = $last_insert_ids_list;
        }
        return $last_insert_ids;
    }

    public function crudReadModel()
    {
        $crud_primary_keys = array();
        // read last 3 inserted elements
        for ($i = 0; $i < $this->testCrud_nb_tries; ++$i) {
            if (empty($crud_primary_keys[$i])) {
                $crud_primary_keys[$i] = array();
            }
            foreach ($this->testCrud_last_insert_ids[$i] as $pktable => $pktable_ids) {
                foreach ($pktable_ids as $pkfield => $pkval) {
                    $crud_primary_keys[$i][$pktable . '-' . $pkfield] = $pkval;
                }
            }
            $crud_read_elements = $this->testCrud_crud->get($crud_primary_keys[$i]);
            $this->assertTrue(is_array($crud_read_elements));
            $crud_read_element = array_values(array_slice($crud_read_elements, 0, 1))[0];
            $fake_object_i = $this->testCrud_fake_object;
            foreach ($fake_object_i as $field => &$val) {
                if (is_string($val)) {
                    $val = str_replace('$i', $i, $val);
                    $val = str_replace('$test_uniqid', $this->testCrud_test_uniqid, $val);
                }
                $dot_fieldname = str_replace('-', '.', $field);
                $this->assertEquals($crud_read_element[$dot_fieldname], $val);
            }
        }
        return $crud_primary_keys;
    }

    // fake_updates: tableau associatif représentant les champs à mettre à jour, sous la forme 'table-champ' => 'valeur'. si valeur est un couple [ 'insecure' => 'bla<script>bla', 'secure' => 'blabla' ] on fournit 'insecure' lors de l'update et on s'assure que la valeur lue après update est bien 'secure'. cela permet de tester les fonctions sanitize
    public function crudUpdateModel($fake_updates)
    {
        $crud_primary_keys = $this->testCrud_crud_primary_keys;
        //$crud_updated_elements = array();
        // update 3 elements
        for ($i = 0; $i < $this->testCrud_nb_tries; ++$i) {
            $fake_updates_i = $fake_updates;
            $fake_updates_i_insecure = array();
            foreach ($fake_updates_i as $field => &$val) {
                if (is_string($val)) {
                    $val = str_replace('$i', $i, $val);
                    $val = str_replace('$test_uniqid', $this->testCrud_test_uniqid, $val);
                    $fake_updates_i_insecure[$field] = $val;
                }
                if (is_array($val) && !empty($val['secure'] && !empty($val['insecure']))) {
                    if (is_string($val['secure'])) {
                        $val['secure'] = str_replace('$i', $i, $val['secure']);
                        $val['secure'] = str_replace('$test_uniqid', $this->testCrud_test_uniqid, $val['secure']);
                    }
                    if (is_string($val['insecure'])) {
                        $val['insecure'] = str_replace('$i', $i, $val['insecure']);
                        $val['insecure'] = str_replace('$test_uniqid', $this->testCrud_test_uniqid, $val['insecure']);
                    }
                    $fake_updates_i_insecure[$field] = $val['insecure'];
                }
            }
            $crud_updated = $this->testCrud_crud->update($fake_updates_i_insecure, $crud_primary_keys[$i]);
            $this->assertTrue($crud_updated);
            $crud_updated_elements_list = $this->testCrud_crud->get($crud_primary_keys[$i]);
            $this->assertTrue(is_array($crud_updated_elements_list));
            $crud_updated_element = array_values(array_slice($crud_updated_elements_list, 0, 1))[0];
            //$crud_updated_elements[$i] = $crud_updated_element;
            $fake_object_i = $this->testCrud_updated_objects[$i];
            $fake_updates_i = $fake_updates;
            foreach ($fake_object_i as $field => &$val) {
                if (is_string($val)) {
                    $val = str_replace('$i', $i, $val);
                    $val = str_replace('$test_uniqid', $this->testCrud_test_uniqid, $val);
                }
            }
            foreach ($fake_updates_i as $field => &$val) {
                if (is_string($val)) {
                    $val = str_replace('$i', $i, $val);
                    $val = str_replace('$test_uniqid', $this->testCrud_test_uniqid, $val);
                    $fake_object_i[$field] = $val;
                }
                if (is_array($val) && !empty($val['secure'] && !empty($val['insecure']))) {
                    if (is_string($val['secure'])) {
                        $val['secure'] = str_replace('$i', $i, $val['secure']);
                        $val['secure'] = str_replace('$test_uniqid', $this->testCrud_test_uniqid, $val['secure']);
                    }
                    if (is_string($val['insecure'])) {
                        $val['insecure'] = str_replace('$i', $i, $val['insecure']);
                        $val['insecure'] = str_replace('$test_uniqid', $this->testCrud_test_uniqid, $val['insecure']);
                    }
                    $fake_object_i[$field] = $val['secure'];
                }
            }
            foreach ($fake_object_i as $field => &$val) {
                $dot_fieldname = str_replace('-', '.', $field);
                $this->assertEquals($crud_updated_element[$dot_fieldname], $val);
            }
            $this->testCrud_updated_objects[$i] = $fake_object_i;
        }
    }

    public function crudDeleteModel()
    {
        // delete 3 elements
        for ($i = 0; $i < $this->testCrud_nb_tries; ++$i) {
            $crud_element_was_deleted = $this->testCrud_crud->delete($this->testCrud_crud_primary_keys[$i]);
            $this->assertTrue($crud_element_was_deleted === true);
            $crud_deleted_element = $this->testCrud_crud->get($this->testCrud_crud_primary_keys[$i]);
            $this->assertTrue($crud_deleted_element === array());
        }
    }

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
        $this->assertTrue($ormModel->sanitizeString(07) === '7');
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
            2 => '✓ this is a string',
            3 => '✓ this is <br />some &laquo; html &raquo;',
            'table.varchar1' => null,
            'table.varchar2' => '',
            'table.varchar3' => 1,
            'table.varchar4' => '02',
            'table.varchar5' => '✓ this is <strong>some</strong> &laquo; html &raquo;',
            'table.varchar6' => array(
                1,
                '✓ this is <strong>some</strong> &laquo; html &raquo;',
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
            2 => '✓ this is a string',
            3 => '✓ this is some &laquo; html &raquo;',
            'table.varchar1' => '',
            'table.varchar2' => '',
            'table.varchar3' => '1',
            'table.varchar4' => '02',
            'table.varchar5' => '✓ this is some &laquo; html &raquo;',
            'table.varchar6' => array(
                '1',
                '✓ this is some &laquo; html &raquo;',
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
