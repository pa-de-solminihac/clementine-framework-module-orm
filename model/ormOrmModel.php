<?php
class ormOrmModel extends ormOrmModel_Parent
{

    /**
     * NOTE: utiliser plusieurs tables ne fonctionne bien que dans le cas de
     * tables en relation 1-1 (car les jointures sont en INNER JOIN)
     * Pour les tables n'entrant pas dans ce cadre, on peut toujours les
     * gérer en dehors, en passant par une surcharge. Ainsi, on utilisera ORM
     * pour les tables en relation 1-1, et on gèrera les autres tables dans la
     * surcharge.
     */

    /* tables : array(
     *     'client',
     *     'adresse');
     *
     * remarque : dans le cas d'une jointure entre plusieurs tables, l'ordre
     *            est important : les insertions seront effectuées d'abord
     *            dans la dernière table, puis en remontant jusqu'à la
     *            première, afin de déterminer les valeurs des champs
     *            auto_increment
     */
    public $tables;

    /* fields : array(
     *     'client' => array(
     *         'nom' => 'text',
     *         'prenom' => 'text'
     *     ),
     *     'adresse' => array(
     *         'nom' => 'text',
     *         'description' => 'textarea',
     *         'code_postal' => 'text'
     *     ));
     */
    public $fields;

    /**
     * group_by : destiné aux surcharges, si nécessaire
     *
     * @var mixed
     * @access public
     */
    public $group_by = array();

    /* metas : array(
     *     'primary_key' => array(
     *         'client'  => array('id_client'),
     *         'adresse' => array('id_adresse')));
     */
    public $metas = array(
        'primary_key' => array(),
        'foreign_keys' => array(),
        'keys_labels' => array(),   // pour les libellés des clés étrangères dans les tag option du tag select généré
        'keys_to_ignore' => array(),
        'readonly_tables' => array(),
        'table_aliases' => array(),
        'hidden_fields' => array(),
        'search_fields' => array(), // champs dans lesquels chercher
        'custom_fields' => array(), // champs a rajouter dans la requete SELECT de base (pour utilisation dans un WHERE, un ORDER BY...)
        'custom_search' => array(), // champs dans lesquels chercher à la place du champ demandé (pour adapter la recherche, dans le cas d'un custom_field utilisant GROUP_CONCAT...)
        'custom_order_by' => array(), // tableau associatif permettant de remplacer "ORDER BY key" par "ORDER BY val"
        'title_mapping' => array());

    /**
     * _init : fonction à surcharger, appelee par le constructeur
     *         passer par une fonction plutot que par les donnees membres
     *         directement est plus flexible (on peut charger des modeles...)
     *
     * @return void
     */
    public function _init($params = null)
    {
        $this->tables = null;
        $this->fields = null;
        /*// remarque : l'ordre est important ici, afin de déterminer les valeurs des champs auto_increment*/
        /*// les insertions sont effectuees d'abord dans la dernière table, puis en remontant table par table jusqu'à la première*/
        /*$this->tables = array(*/
            /*'andco_om_has_users' => '',                                                                                                     // pas de champ AI a determiner*/
            /*'andco_om_has_villes' => array('inner join' => "`andco_om_has_users`.`andco_om_id` = `andco_om_has_villes`.`andco_om_id` "),    // pas de champ AI a determiner*/
            /*'andco_om' => array('inner join' => "`andco_om_has_users`.`andco_om_id` = `andco_om`.`id` AND `createur_om` = '1' "),           // determine le champ AI andco_om.id*/
            /*'clementine_adresse' => array('inner join' => '`andco_om`.`clementine_adresse_id` = `clementine_adresse`.`id`'));               // determine le champ AI clementine_adresse.id*/
        /*// remarque : comme les clés étrangères ne sont pas spécifiées dans la base de données, on doit les expliciter*/
        /*$this->metas['foreign_keys'] = array(*/
            /*'andco_om.clementine_adresse_id'      => 'clementine_adresse.id', // clé étrangère => clé primaire sur laquelle elle pointe*/
            /*'andco_om_has_villes.andco_om_id'     => 'andco_om.id',           // clé étrangère => clé primaire sur laquelle elle pointe*/
            /*'andco_om_has_villes.andco_villes_id' => 'andco_villes.id',       // clé étrangère => clé primaire sur laquelle elle pointe*/
            /*'andco_om_has_users.andco_om_id'      => 'andco_om.id'            // clé étrangère => clé primaire sur laquelle elle pointe*/
        /*);*/
        /*// clés a ignorer car elles ne sont pas utiles dans la cle primaire*/
        /*$this->metas['keys_to_ignore'] = array(*/
            /*'andco_om_has_users.clementine_users_id' => '',*/
            /*'andco_om_has_villes.andco_villes_id' => ''*/
        /*);*/
    }

    /**
     * __construct : recupere les champs, types et cles des tables specifiees dans le tableau $this->tables
     *
     * @access public
     * @return void
     */
    public function __construct($params = null)
    {
        // cette classe est destinee a etre surchargee, elle ne doit servir a rien sinon !
        if (get_class($this) == 'OrmModel' || get_class($this) == 'CrudModel') {
            return false;
        }
        if (!count($this->tables)) {
            $this->_init($params);
        }
        if (count($this->tables)) {
            if (!$this->fields) {
                // custom_fields
                if (!isset($this->metas['custom_fields'])) {
                    $this->metas['custom_fields'] = array();
                } else {
                    foreach ($this->metas['custom_fields'] as $alias => $custom_fields) {
                        $this->fields[$alias] = array('type' => 'custom_field');
                    }
                }
                $db = $this->getModel('db');
                foreach ($this->tables as $table => $join) {
                    $table_array = explode(' ', $table, 3);
                    $cnt_table_array = count($table_array);
                    $table_real = $table_array[0];
                    if ($cnt_table_array > 1) {
                        $table_alias = $table_array[$cnt_table_array - 1];
                        // on stocke l'association table_alias => table_real dans les metas
                        $this->metas['table_aliases'][$table_alias] = $table_real;
                    } else {
                        $table_alias = $table_real;
                    }
                    $champs = $db->list_fields($table_real);
                    foreach ($champs as $champ) {
                        $type = preg_replace('/[( ].*/', '', $champ['Type']);
                        // cas particulier boolean vu comme tinyint
                        if (strpos('tinyint(1)', $champ['Type']) === 0) {
                            $type = 'boolean';
                        }
                        $size = (int) preg_replace('/^[^\(]*\(/', '', $champ['Type']);
                        if (!isset($this->fields[$table_alias . '.' . $champ['Field']])) {
                            $this->fields[$table_alias . '.' . $champ['Field']] = array();
                        }
                        $this->fields[$table_alias . '.' . $champ['Field']]['type'] = $type;
                        $this->fields[$table_alias . '.' . $champ['Field']]['size'] = $size;
                        // charge les valeurs possibles pour les type enum et set
                        if ($type == 'enum' || $type == 'set') {
                            $enum_array = array();
                            $champ_type = $champ['Type'];
                            $champ_type = substr($champ_type, (strlen($type) + 2), -2); // supprime les extremites "enum('" et "')" de la chaine
                            $champ_type = str_replace("''", "\\'", $champ_type);
                            $enum_array = explode("','", $champ_type);
                            $values = array();
                            foreach ($enum_array as $val) {
                                $values[stripslashes($val)] = stripslashes($val);
                            }
                            $this->fields[$table_alias . '.' . $champ['Field']]['fieldvalues'] = $values;
                        }
                        // charge les commentaires des champs
                        if (isset($champ['Comment']) && strlen($champ['Comment'])) {
                            $this->fields[$table_alias . '.' . $champ['Field']]['comment'] = $champ['Comment'];
                        }
                        // primary key
                        if ($champ['Key'] == 'PRI') {
                            $this->metas['primary_key'][$table_alias][$champ['Field']] = $champ['Extra'];
                            // hidden fields : primary key || keys_to_ignore
                            if ($champ['Extra'] == 'auto_increment' || isset($this->metas['keys_to_ignore'][$table_alias . '.' . $champ['Field']])) {
                                if (!isset($this->metas['hidden_fields'][$table_alias . '.' . $champ['Field']])) {
                                    $this->metas['hidden_fields'][$table_alias . '.' . $champ['Field']] = array();
                                }
                                $this->metas['hidden_fields'][$table_alias . '.' . $champ['Field']]['flag'] = $champ['Extra'];
                            }
                        }
                    }
                    // foreign keys
                    $foreign_keys = $db->foreign_keys($table_real);
                    foreach ($foreign_keys as $fkeys) {
                        // on n'ecrase l'info sur cette clé étrangère que si on ne l'a pas fournie manuellement
                        if (!isset($this->metas['foreign_keys'][$fkeys['foreign_key']])) {
                            $this->metas['foreign_keys'][$fkeys['foreign_key']] = $fkeys['references'];
                        }
                    }
                }
                // hidden fields : foreign_key referencant une des tables courantes
                foreach ($this->fields as $tablefield => $fieldmeta) {
                    if (isset($this->metas['hidden_fields'][$tablefield])) {
                        $this->metas['hidden_fields'][$tablefield] = array_merge($this->metas['hidden_fields'][$tablefield], $fieldmeta);
                    } else {
                        if ($fieldmeta['type'] != 'custom_field') {
                            list ($table, $field) = explode('.', $tablefield, 2);
                            if (isset($this->metas['readonly_tables'][$table])) {
                                if (isset($this->metas['readonly_tables'][$table])) {
                                    $this->metas['hidden_fields'][$tablefield] = $fieldmeta;
                                }
                            }
                        }
                        if (isset($this->metas['foreign_keys'][$tablefield])) {
                            $references = explode('.', $this->metas['foreign_keys'][$tablefield], 2);
                            $table_name_and_alias = $references[0];
                            if (isset($this->metas['table_aliases'][$references[0]])) {
                                $table_name_and_alias = $this->metas['table_aliases'][$references[0]] . ' ' . $references[0];
                            }
                            if (isset($this->tables[$table_name_and_alias])) {
                                $this->metas['hidden_fields'][$tablefield] = $fieldmeta;
                            }
                        }
                    }
                }
            }
        } else {
            if (__DEBUGABLE__ && get_class($this) != 'OrmModel' && get_class($this) == 'CrudModel') {
                $this->getHelper('debug')->orm_constructor();
            }
            return false;
        }
    }

    public function addCustomField($name, $field_sql_definitions)
    {
        $this->fields[$name] = array('type' => 'custom_field');
        $this->metas['custom_fields'][$name] = $field_sql_definitions['sql_definition'];
        if (isset($field_sql_definitions['custom_search'])) {
            $this->metas['custom_search'][$name] = $field_sql_definitions['custom_search'];
        }
        if (isset($field_sql_definitions['custom_order_by'])) {
            $this->metas['custom_order_by'][$name] = $field_sql_definitions['custom_order_by'];
        }
    }

    /**
     * getList : liste des enregistrements dans un tableau associatif
     *           (chaque clé du tableau est la clé primaire
     *            correspondante au niveau BD)
     *
     * @access public
     * @return void
     */
    public function getList($params = null)
    {
        $ns = $this->getModel('fonctions');
        $db = $this->getModel('db');
        // recupere la liste des elements a afficher
        $sql = $this->getBaseSelectFromThis($params);
        if (isset($params['where'])) {
            $sql .= "\nAND " . $params['where'] . ' ';
        }
        if ($this->group_by) {
            $sql .= "\nGROUP BY " . implode(",\n    ", array_unique($this->group_by));
        }
        if (isset($params['order_by'])) {
            $sql .= "\nORDER BY " . $params['order_by'] . ' ';
        }
        if (!empty($params['limit'])) {
            $sql .= "\nLIMIT " . $params['limit'] . ' ';
        }
        $result = array();
        for ($stmt = $db->query($sql); $res = $db->fetch_assoc($stmt); ) {
            $primary_key = '';
            // parametres pour la cle primaire (pour les liens)
            foreach ($this->fields as $tablefield => $fieldmeta) {
                if ($fieldmeta['type'] != 'custom_field') {
                    list ($table, $field) = explode('.', $tablefield, 2);
                    if (isset($this->metas['primary_key'][$table][$field]) && !isset($this->metas['keys_to_ignore'][$table . '.' . $field])) {
                        $primary_key .= '&' . $table . '-' . $field
                            . '=' . $res[$table . "." . $field];
                    }
                }
            }
            $primary_key = $ns->substr($primary_key, 1);
            $result[$primary_key] = $res;
        }
        return $result;
    }

    /**
     * get : renvoie l'enregistrement déterminé par la clé primaire
     *
     * @param mixed $insecure_primary_key : tableau associatif 'champ de la clé primaire' => 'valeur'
     * @access public
     * @return void
     */
    public function get($insecure_primary_key, $params = null)
    {
        $ns = $this->getModel('fonctions');
        $db = $this->getModel('db');
        $secure_primary_key = $this->sanitizePrimaryKey($insecure_primary_key);
        $sql = $this->getBaseSelectFromThis($params);
        $sql .= "\nAND " . $this->getPKSql($secure_primary_key);
        if (isset($params['where'])) {
            $sql .= "\nAND " . $params['where'] . ' ';
        }
        if ($this->group_by) {
            $sql .= "\nGROUP BY " . implode(",\n    ", array_unique($this->group_by));
        }
        $result = array();
        for ($stmt = $db->query($sql); $res = $db->fetch_assoc($stmt); ) {
            $primary_key = '';
            // parametres pour la cle primaire (pour les liens)
            foreach ($this->fields as $tablefield => $fieldmeta) {
                if ($fieldmeta['type'] != 'custom_field') {
                    list ($table, $field) = explode('.', $tablefield, 2);
                    if (isset($this->metas['primary_key'][$table][$field]) && !isset($this->metas['keys_to_ignore'][$table . '.' . $field])) {
                        $primary_key .= '&' . $table . '-' . $field
                            . '=' . $res[$table . "." . $field];
                    }
                }
            }
            $primary_key = $ns->substr($primary_key, 1);
            $result[$primary_key] = $res;
        }
        return $result;
    }

    /**
     * create : crée un enregistrement à partir des valeurs de $insecure_values
     *
     * @param mixed $insecure_values : tableau associatif 'table-champ' => 'valeur'
     * @access public
     * @return void
     */
    public function create($insecure_values, $params = null)
    {
        $module_name = $this->getCurrentModule();
        $err = $this->getHelper('errors');
        $ns = $this->getModel('fonctions');
        $db = $this->getModel('db');
        $secure_values = $this->sanitizeValues($insecure_values);
        $last_insert_ids = array();
        $tables = array_reverse($this->tables);
        $values = array();
        foreach ($this->fields as $tablefield => $fieldmeta) {
            if ($fieldmeta['type'] != 'custom_field') {
                list ($table, $field) = explode('.', $tablefield, 2);
                if (isset($secure_values[$table . '-' . $field])) {
                    $values[$tablefield] = $secure_values[$table . '-' . $field];
                }
            }
        }
        $errors = array();
        if (empty($params['dont_start_transaction'])) {
            $db->query('START TRANSACTION');
        }
        foreach ($tables as $table => $join) {
            $table_array = explode(' ', $table, 3);
            $cnt_table_array = count($table_array);
            $table_real = $table_array[0];
            if ($cnt_table_array > 1) {
                $table_alias = $table_array[$cnt_table_array - 1];
            } else {
                $table_alias = $table_real;
            }
            if (isset($this->metas['readonly_tables'][$table_real]) || isset($this->metas['readonly_tables'][$table_alias])) {
                continue;
            }
            $table_fields = array();
            foreach ($this->fields as $tablefield => $fieldmeta) {
                if ($fieldmeta['type'] != 'custom_field') {
                    list ($tftable, $tffield) = explode('.', $tablefield, 2);
                    if ($table_alias == $tftable) {
                        $table_fields[] = $tablefield;
                    }
                }
            }
            // escape values
            $table_values = array();
            foreach ($values as $tablefield => $val) {
                list ($tftable, $tffield) = explode('.', $tablefield, 2);
                if ($table_alias == $tftable) {
                    $table_values[$tablefield] = $db->escape_string($val);
                }
            }
            // essaye de remplacer les valeurs des cles etrangeres qui referencent un champ autoincrement
            foreach ($table_fields as $table_field) {
                if (isset($this->metas['foreign_keys'][$table_field])) {
                    $references = explode('.', $this->metas['foreign_keys'][$table_field], 2);
                    if (isset($last_insert_ids[$references[0]][$references[1]])) {
                        // remplace si possible pour le champ qui est une foreign key la valeur du champ autoincrement qu'elle reference
                        $table_values[$table_field] = $last_insert_ids[$references[0]][$references[1]];
                    }
                }
            }
            $fieldssql_array = array();
            foreach ($table_fields as $table_field) {
                if (isset($table_values[$table_field]) || isset($this->metas['primary_key'][$table_alias][$table_field])) {
                    list ($tftable, $tffield) = explode('.', $table_field, 2);
                    $fieldssql_array[$tffield] = $tffield;
                }
            }
            // verifie qu'on a suffisament de champs pour tenter l'insertion
            if ($fieldssql_array) {
                foreach ($this->metas['primary_key'][$table_alias] as $pkfield => $pktype) {
                    if (!isset($fieldssql_array[$pkfield]) && $pktype != 'auto_increment') {
                        if (!isset($this->metas['keys_to_ignore'][$table_alias . '.' . $pkfield])) {
                            $errmsg = "Missing value for field $pkfield (which is part of the primary key for table). If it is a foreign_key, check that it points to a field known by \$last_insert_ids \r\n";
                            $errors[] = $errmsg;
                            $err->register_err('create', 'missing_value', $errmsg, $module_name);
                        } else {
                            continue 2;
                        }
                    }
                }
            } else {
                continue;
            }
            $fieldssql = '`' . implode('`,`', $fieldssql_array) . '`';
            // construction de la requete SQL
            $sql = 'INSERT INTO `' . $table_real . "` (" . $fieldssql . ") VALUES (";
            $insertsql = '';
            foreach ($table_fields as $table_field) {
                if (isset($table_values[$table_field]) || isset($this->metas['primary_key'][$table_alias][$table_field])) {
                    if (isset($table_values[$table_field])) {
                        $insertsql .= "'" . $table_values[$table_field] . "', ";
                    } else if (isset($this->metas['primary_key'][$table_alias][$table_field])) {
                        $insertsql .= "'', ";
                    }
                }
            }
            $insertsql = $ns->substr($insertsql, 0, -2);
            $sql .= $insertsql;
            $sql .= ") ";
            // on ne prend que la cle primaire de la table $table_real
            // try a non fatal query (if duplicate key, just rollback)
            if ($db->query($sql, true) === false) {
                $errmsg = 'Error while saving values in table "' . $table_alias . '"';
                $errors[] = $errmsg;
                $err->register_err('create', 'saving_values', $errmsg, $module_name);
            } else {
                $ai_field = array_search('auto_increment', $this->metas['primary_key'][$table_alias]);
                if ($ai_field) {
                    $last_insert_ids[$table_alias] = array($ai_field => $db->insert_id());
                }
            }
        }
        if (count($errors)) {
            if (empty($params['dont_start_transaction'])) {
                $db->query('ROLLBACK');
            }
            return false;
        } else {
            if (empty($params['dont_start_transaction'])) {
                $db->query('COMMIT');
            }
            if (count($last_insert_ids)) {
                return $last_insert_ids;
            }
            return true;
        }
    }

    /**
     * update : met a jour l'enregistrement identifié par $insecure_primary_key
     *                   à partir des valeurs de $insecure_values
     *
     * @param mixed $insecure_values : tableau associatif 'table-champ' => 'valeur', par exemple $_POST
     * @param mixed $insecure_primary_key : tableau associatif 'table-champ' => 'valeur', par exemple $_GET
     * @access public
     * @return void
     */
    public function update($insecure_values, $insecure_primary_key = null, $params = null)
    {
        $ns = $this->getModel('fonctions');
        $db = $this->getModel('db');
        // si on fournit directement l'objet a enregistrer, il contient déjà la clé primaire donc on peut la récupérer
        if (!isset($insecure_primary_key)) {
            $PK = array();
            foreach ($this->metas['primary_key'] as $table => $keys) {
                foreach ($keys as $key => $type) {
                    if (!isset($this->metas['keys_to_ignore'][$table . '.' . $key])) {
                        $PK[$table . '-' . $key] = '';
                    }
                }
            }
            $PK_filled = array();
            $PK_missingfields = array();
            foreach ($PK as $tablefield => $val) {
                if (!(isset($insecure_values[$tablefield]))) {
                    $PK_missingfields[$tablefield] = '';
                } else {
                    $PK_filled[str_replace('.', '-', $tablefield)] = $insecure_values[$tablefield];
                }
            }
            if (count($PK_missingfields)) {
                if (__DEBUGABLE__ && get_class($this) != 'OrmModel' && get_class($this) != 'CrudModel') {
                    echo ('Missing fields : ' . implode(', ', array_keys($PK_missingfields)));
                }
                return false;
            } else {
                $insecure_primary_key = $PK_filled;
            }
            // attention : si on appelle update() sur un tableau sans $insecure_primary_key c'est aussi que les noms des champs ne sont pas dans le bon format
            // en attendant d'avoir remis à plat les noms des champs (utiliser '-' partout, ou '.' partout...) on "convertit" ici les noms des champs (c'est un peu sale)
            $new_insecure_values = array();
            foreach ($insecure_values as $key => $val) {
                $new_insecure_values[str_replace('.', '-', $key)] = $val;
            }
            $insecure_values = $new_insecure_values;
        }
        $secure_primary_key = $this->sanitizePrimaryKey($insecure_primary_key);
        // important pour la sécurité : verifie si la cle primaire est suffisante... sinon on n'identifie pas completement !
        $secure_primary_key = $this->completePrimaryKey($secure_primary_key);
        if (!$secure_primary_key) {
            return false;
        }
        // pour faciliter l'utilisation : on merge $insecure_primary_key dans $insecure_values
        // ainsi, il n'est pas necessaire de respécifier les champs de la clé primaire dans les champs de données
        $insecure_values = array_merge($insecure_values, $secure_primary_key);
        $secure_values = $this->sanitizeValues($insecure_values);
        $tables = array_reverse($this->tables);
        $values = array();
        foreach ($this->fields as $tablefield => $fieldmeta) {
            if ($fieldmeta['type'] != 'custom_field') {
                list ($table, $field) = explode('.', $tablefield, 2);
                if (isset($secure_values[$table . '-' . $field])) {
                    $values[$tablefield] = $secure_values[$table . '-' . $field];
                }
            }
        }
        $errors = array();
        if (empty($params['dont_start_transaction'])) {
            $db->query('START TRANSACTION');
        }
        foreach ($tables as $table => $join) {
            $table_array = explode(' ', $table, 3);
            $cnt_table_array = count($table_array);
            $table_real = $table_array[0];
            if ($cnt_table_array > 1) {
                $table_alias = $table_array[$cnt_table_array - 1];
            } else {
                $table_alias = $table_real;
            }
            if (isset($this->metas['readonly_tables'][$table_real]) || isset($this->metas['readonly_tables'][$table_alias])) {
                continue;
            }
            $table_fields = array();
            foreach ($this->fields as $tablefield => $fieldmeta) {
                if ($fieldmeta['type'] != 'custom_field') {
                    list ($tftable, $tffield) = explode('.', $tablefield, 2);
                    if ($table_alias == $tftable) {
                        $table_fields[] = $tablefield;
                    }
                }
            }
            // escape values
            $table_values = array();
            foreach ($values as $tablefield => $val) {
                list ($tftable, $tffield) = explode('.', $tablefield, 2);
                if ($table_alias == $tftable) {
                    $table_values[$tablefield] = $db->escape_string($val);
                }
            }
            // construction de la requete SQL
            $sql = 'UPDATE `' . $table_real . '` ';
            if ($table_alias != $table_real) {
                $sql .= $table_alias . ' ';
            }
            $sql .= 'SET ';
            $updatesql = '';
            foreach ($table_fields as $table_field) {
                list ($tftable, $tffield) = explode('.', $table_field, 2);
                if ($table_alias == $tftable) {
                    if (isset($secure_values[$tftable . '-' . $tffield])) {
                        // on utilise ici l'alias si possible, car le where est genere avec l'alias
                        if (null == $table_values[$table_field]) {
                            $updatesql .= ', `' . $table_alias . '`.`' . $tffield . "` = NULL ";
                        } else {
                            $updatesql .= ', `' . $table_alias . '`.`' . $tffield . "` = '" . $table_values[$table_field] . "'";
                        }
                    }
                }
            }
            $updatesql = $ns->substr($updatesql, 2);
            $sql .= $updatesql;
            // on ne prend que la cle primaire de la table $table_real
            $table_pk = array();
            $morewhere = '';
            foreach ($secure_primary_key as $key => $val) {
                $tab = explode('-', $key, 2);
                if ($tab[0] == $table_alias) {
                    if (isset($this->metas['primary_key'][$table_alias][$tab[1]])) {
                       $table_pk[$key] = $val;
                    } else {
                        $morewhere .= ' AND `' . $tab[0] . '`.`' . $tab[1] . "` = '" . $db->escape_string($val) . "' ";
                    }
                }
            }
            $sql .= ' WHERE ' . $this->getPKSql($table_pk);
            $sql .= $morewhere;
            $sql .= ' LIMIT 1 ';
            // try a non fatal query (if duplicate key, just rollback)
            if ($db->query($sql, false) === false) {
                $errors[] = 'Error while saving values in table "' . $table_alias . '"';
            }
        }
        if (!count($errors)) {
            if (empty($params['dont_start_transaction'])) {
                $db->query('COMMIT');
            }
            return true;
        } else {
            if (empty($params['dont_start_transaction'])) {
                $db->query('ROLLBACK');
            }
            return false;
        }
    }

    /**
     * search : recherche les enregistrements qui correspondent aux valeurs de $insecure_values
     *
     * @param mixed $insecure_values : tableau associatif 'table-champ' => 'valeur', par exemple $_POST
     * @param mixed $params : tableau d'options, qui seront également transmises à getList
     * @access public
     * @return void
     */
    public function search($insecure_values, $params = null)
    {
        $db = $this->getModel('db');
        $search_params_elements = array();
        $comparator = "=";
        if (!empty($params['comparison_operator'])) {
            $comparator = $params['comparison_operator'];
        }
        foreach ($insecure_values as $key => $val) {
            $search_params_elements[] = "
                $key " . $comparator ." '" . $db->escape_string($val) . "'
            ";
        }
        $search_params = $params;
        if (empty($search_params['where'])) {
            $search_params['where'] = '';
        }
        $search_params['where'] .= implode(' AND ', $search_params_elements);
        return $this->getList($search_params);
    }

    /**
     * delete : supprime l'enregistrement identifié par $insecure_primary_key
     *
     * @param mixed $insecure_primary_key
     * @access public
     * @return void
     */
    public function delete($insecure_primary_key, $params = null)
    {
        $db = $this->getModel('db');
        $secure_primary_key = $this->sanitizePrimaryKey($insecure_primary_key);
        // important pour la sécurité : verifie si la cle primaire est suffisante... sinon on n'identifie pas completement !
        $secure_primary_key = $this->completePrimaryKey($secure_primary_key);
        if (!$secure_primary_key) {
            return false;
        }
        $errors = array();
        if (empty($params['dont_start_transaction'])) {
            $db->query('START TRANSACTION');
        }
        $tables = array_reverse($this->tables);
        foreach ($tables as $table => $join) {
            $table_array = explode(' ', $table, 3);
            $cnt_table_array = count($table_array);
            $table_real = $table_array[0];
            if ($cnt_table_array > 1) {
                $table_alias = $table_array[$cnt_table_array - 1];
            } else {
                $table_alias = $table_real;
            }
            if (isset($this->metas['readonly_tables'][$table_real]) || isset($this->metas['readonly_tables'][$table_alias])) {
                continue;
            }
            $table_fields = array();
            foreach ($this->fields as $tablefield => $fieldmeta) {
                if ($fieldmeta['type'] != 'custom_field') {
                    list ($tftable, $tffield) = explode('.', $tablefield, 2);
                    if ($table == $tftable) {
                        $table_fields[] = $tablefield;
                    }
                }
            }
            $sql = 'DELETE FROM `' . $table_real . '` ';
            // on ne prend que la cle primaire de la table $table
            $table_pk = array();
            foreach ($secure_primary_key as $key => $val) {
                $tab = explode('-', $key, 2);
                if ($tab[0] == $table_alias && isset($this->metas['primary_key'][$table_alias][$tab[1]])) {
                    $table_pk[$key] = $val;
                }
            }
            $sql .= ' WHERE ' . $this->getPKSql($table_pk, true);
            $sql .= ' LIMIT 1 ';
            if ($db->query($sql) === false) {
                $errors[] = 'Error while deleting from table "' . $table_alias . '"';
            }
        }
        if (!count($errors)) {
            if (empty($params['dont_start_transaction'])) {
                $db->query('COMMIT');
            }
            return true;
        } else {
            if (empty($params['dont_start_transaction'])) {
                $db->query('ROLLBACK');
            }
            return false;
        }
    }

    /**
     * sanitizeValues : filtre les valeurs du tableau $insecure_array
     *                  avec la fonction strip_tags et renvoie le tableau filtré
     *
     * @param mixed $insecure_array
     * @access public
     * @return void
     */
    public function sanitizeValues($insecure_array = null)
    {
        $ns = $this->getModel('fonctions');
        $secure_array = array();
        if (isset($insecure_array)) {
            foreach ($insecure_array as $key => $val) {
                $field = str_replace('-', '.', $key);
                if (!empty($this->fields[$field]['type'])) {
                    $type = $this->fields[$field]['type'];
                    switch ($type) {
                    case 'tinyint':
                    case 'int':
                    case 'smallint':
                    case 'mediumint':
                    case 'bigint':
                            if (is_array($val)) {
                                foreach ($val as $subkey => $subval) {
                                    $secure_array[$key][$subkey] = (int)$subval;
                                }
                            } else {
                                $secure_array[$key] = (int)$val;
                            }
                        break;
                    case 'bit':
                    case 'boolean':
                            if (is_array($val)) {
                                foreach ($val as $subkey => $subval) {
                                    $secure_array[$key][$subkey] = (int)((bool)$subval);
                                }
                            } else {
                                $secure_array[$key] = (int)((bool)$val);
                            }
                        break;
                    default:
                            if (is_array($val)) {
                                foreach ($val as $subkey => $subval) {
                                    $secure_array[$key][$subkey] = $ns->strip_tags($subval);
                                }
                            } else {
                                $secure_array[$key] = $ns->strip_tags($val);
                            }
                        break;
                    }
                } else {
                    if (is_array($val)) {
                        foreach ($val as $subkey => $subval) {
                            $secure_array[$key][$subkey] = $ns->strip_tags($subval);
                        }
                    } else {
                        $secure_array[$key] = $ns->strip_tags($val);
                    }
                }
            }
        }
        return $secure_array;
    }

    /**
     * sanitizePrimaryKey : alias de sanitizeValues
     *                      utiliser un alias permet de faciliter la surcharge
     *
     * @param mixed $insecure_array
     * @access public
     * @return void
     */
    public function sanitizePrimaryKey($insecure_array)
    {
        return $this->sanitizeValues($insecure_array);
    }

    /**
     * getBaseSelectFromThis : returns base SQL query (retrieves all the rows)
     *
     * @access public
     * @return void
     */
    public function getBaseSelectFromThis($params = null)
    {
        $ns = $this->getModel('fonctions');
        $select_fields = '';
        foreach ($this->fields as $tablefield => $fieldmeta) {
            if ($fieldmeta['type'] != 'custom_field') {
                list ($tftable, $tffield) = explode('.', $tablefield, 2);
                $select_fields .= "\n    `" . $tftable . '`.`' . $tffield . '` AS "' . $tablefield . '", ';
            }
        }
        // ajoute les champs de custom_fields au SELECT généré
        foreach ($this->metas['custom_fields'] as $alias => $custom_field) {
            $select_fields .= "\n    " . $custom_field . ' AS "' . $alias . '", ';
        }
        $select_fields = $ns->substr($select_fields, 0, -2);
        $sql = 'SELECT ';
        if (isset($params['sql_calc_found_rows']) && $params['sql_calc_found_rows']) {
            $sql .= ' SQL_CALC_FOUND_ROWS ';
        }
        $sql .= $select_fields . "\nFROM ";
        foreach ($this->tables as $table => $join) {
            $table_array = explode(' ', $table, 3);
            $cnt_table_array = count($table_array);
            $table_real = $table_array[0];
            if ($cnt_table_array > 1) {
                $table_alias = $table_array[$cnt_table_array - 1];
            } else {
                $table_alias = $table_real;
            }
            $nb_join = count($join);
            if ($nb_join && isset($join['inner join'])) {
                    $sql .= "\n    INNER JOIN `" . $table_real . '` ';
                    if ($table_alias != $table_real) {
                        $sql .= $table_alias . ' ';
                    }
                    $sql .= "\n        ON " . $join['inner join'] . ' ';
            // pour les left join : uniquement dans le cas ou on ne modifie pas de tables ! c'est juste un moyen de charger des donnees supplémentaires facultatives
            } elseif ($nb_join && isset($join['left join'])) {
                if (isset($this->metas['readonly_tables'][$table_real]) || isset($this->metas['readonly_tables'][$table_alias])) {
                    $sql .= "\n    LEFT JOIN `" . $table_real . '` ';
                    if ($table_alias != $table_real) {
                        $sql .= $table_alias . ' ';
                    }
                    $sql .= "\n        ON " . $join['left join'] . ' ';
                    if (isset($join['group by']) && is_array($join['group by']) && count($join['group by'])) {
                        $this->group_by = array_merge($this->group_by, $join['group by']);
                    } else {
                        // on ne peut prendre le left join que si on a un group by pour aller avec (pour s'assurer d'avoir une relation 0-1 et surtout pas 0-n)
                        // donc on genere le group by automatiquement si on n'en a pas fourni un
                        $group_by = array();
                        // desactive car pas assez fiable pour le moment... peut etre ameliorable avec les cles etrangeres ?
                        /*foreach ($this->metas['primary_key'][$table] as $field => $val) {*/
                        /*$group_by[] = $table . '.' . $field;*/
                        /*}*/
                        if (count($group_by)) {
                            $this->group_by = array_merge($this->group_by, $group_by);
                        } else {
                            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                            $err_msg = "You need a 'group by' if you want to use 'left join' for table `$table` (but none could be found automaticaly) ";
                            if (__DEBUGABLE__ && Clementine::$config['clementine_debug']['display_errors']) {
                                Clementine::$register['clementine_debug_helper']->trigger_error($err_msg, E_USER_ERROR, 1);
                            }
                        }
                    }
                } else {
                    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
                    $err_msg = "'left join' is only available for readonly_tables at the moment, and `$table` is not registered as one of them ";
                    if (__DEBUGABLE__ && Clementine::$config['clementine_debug']['display_errors']) {
                        Clementine::$register['clementine_debug_helper']->trigger_error($err_msg, E_USER_ERROR, 1);
                    }
                }
            } else {
                $sql .= "\n`" . $table_real . '` ';
                if ($table_alias != $table_real) {
                    $sql .= $table_alias . ' ';
                }
            }
        }
        $sql .= "\nWHERE 1 ";
        return $sql;
    }

    /*
     * getPKSql : returns the SQL query for the primary key,
     *                     based on the values submitted in $insecure_array
     *
     * @param mixed $insecure_array
     * @access public
     * @return void
     */
    public function getPKSql($insecure_primary_key, $use_table_realname = false)
    {
        $secure_primary_key = $this->sanitizePrimaryKey($insecure_primary_key);
        // récupère les parametres attendus pour la clé primaire
        // dans le tableau $secure_primary_key et en fait une condition SQL
        $db = $this->getModel('db');
        $primary_key = '(1 ';
        $tables = $this->metas['primary_key'];
        foreach ($this->metas['primary_key'] as $table => $fields) {
            foreach ($fields as $field => $fieldmeta) {
                if (isset($secure_primary_key[$table . '-' . $field]) && !isset($this->metas['keys_to_ignore'][$table . '.' . $field])) {
                    $table_name = $table;
                    if ($use_table_realname && isset($this->metas['table_aliases'][$table])) {
                        $table_name = $this->metas['table_aliases'][$table];
                    }
                    $primary_key .= 'AND `' . $table_name . '`.`' . $field . "` = '" . $db->escape_string($secure_primary_key[$table . '-' . $field]) . "' ";
                }
            }
        }
        $primary_key .= ') ';
        return $primary_key;
    }

    /**
     * completePrimaryKey : checks if there is enough fields provided to have an identifier
     *
     * @param mixed $insecure_array
     * @access public
     * @return void
     */
    public function completePrimaryKey($insecure_array)
    {
        // par un select avec jointure, on fait d'une pierre deux coups :
        // - on recupere les elements manquants
        // - on verifie que les informations fournies ne sont pas contradictoires
        // on ne passe pas de parametres supplementaires ici, c'est volontaire
        $secure_array = $this->get($insecure_array);
        if (count($secure_array) == 1) {
            $ns = $this->getModel('fonctions');
            $secure_values = $ns->array_first($secure_array);
            // si un element est manquant...
            foreach ($this->metas['primary_key'] as $table => $fields) {
                foreach ($fields as $field => $fieldmeta) {
                    // ... et si ce n'est pas un element a ignorer...
                    if (!isset($this->metas['keys_to_ignore'][$table . '.' . $field])) {
                        if (!isset($insecure_array[$table . '-' . $field])) {
                            if (isset($secure_values[$table . '.' . $field])) {
                                $insecure_array[$table . '-' . $field] = $secure_values[$table . '.' . $field];
                            } else {
                                $this->getHelper('debug')->orm_missing_primary_key($table, $field);
                                return false;
                            }
                        }
                    }
                }
            }
        } else {
            if (count($secure_array) > 1) {
                $this->getHelper('debug')->orm_incomplete_key();
            } else {
                $this->getHelper('debug')->unknown_element();
            }
            return false;
        }
        return $insecure_array;
    }

}
