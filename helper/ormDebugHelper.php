<?php
class ormDebugHelper extends ormDebugHelper_Parent
{
    public function orm_constructor ()
    {
        if (__DEBUGABLE__ && Clementine::$config['clementine_debug']['display_errors']) {
            $this->trigger_error("ORM constructor : \$this->tables ne doit pas être vide. Surchargez la fonction _init du modèle... ", E_USER_ERROR, 1);
        }
        die();
    }

    public function orm_missing_primary_key ($table, $field)
    {
        if (__DEBUGABLE__ && Clementine::$config['clementine_debug']['display_errors']) {
            $this->trigger_error("ORM missing primary key : " . $table . '.' . $field, E_USER_ERROR, 3);
        }
        die();
    }

    public function orm_incomplete_key ()
    {
        if (__DEBUGABLE__ && Clementine::$config['clementine_debug']['display_errors']) {
            $this->trigger_error("ORM incomplete key", E_USER_WARNING, 3);
        }
    }

    public function unknown_element ()
    {
        if (__DEBUGABLE__ && Clementine::$config['clementine_debug']['display_errors']) {
            $this->trigger_error("ORM unknown element : cet élément n'existe pas ou n'est pas accessible ", E_USER_WARNING, 1);
        }
    }
}
