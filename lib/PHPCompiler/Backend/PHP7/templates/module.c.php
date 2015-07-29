#ifdef HAVE_CONFIG_H
#include "config.h"
#endif
#include "php.h"
#include "php_<?php echo $name; ?>.h"
#include <stdint.h>



<?php
foreach ($classEntries as $entry) { ?>

zend_class_entry *php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_ce;
zend_object_handlers php_{$name}_{$entry['id']}_handlers;
<?php
foreach ($classEntries as $entry) { ?>
HashTable php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_prop_handlers;
<?php
}
?>

#define PHP_<?php echo $uppername; ?>_<?php echo $entry['id']; ?>_OBJ(from) \
	(php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_t*) zendobject_store_get_object((from));

typedef struct _php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_t {
	zend_object std;
<?php
    foreach ($entry['properties'] as $prop) {
        echo "\t{$prop['ctype']} p_{$prop['name']};\n";
    }
    ?>
} php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_t

typedef int(*php_<?php echo $name; ?>_read_t)(php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_t *obj, zval *retval);
typedef int(*php_<?php echo $name; ?>_write_t)(php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_t *obj, zval *newval);

typedef struct _php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_prop_handler {
	php_<?php echo $name; ?>_read_t read_func;
	php_<?php echo $name; ?>_write_t write_func;
} php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_prop_handler;

static void php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_register_prop_handler(HashTable *prop_handler, char *name, php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_read_t read_func, php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_write_t write_func) {
	php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_prop_handler handler;
	handler.read_func = read_func;
	handler.write_func = write_func;
	zend_hash_str_add_mem(prop_handler, name, strlen(name), &hnd, sizeof(php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_prop_handler));
}

static void php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_dtor_prop_handler(zval *zv) {
	free(Z_PTR_P(zv));
}

zval *php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_read_property(zval *object, zval *member, int type, void **cache_slot, zval *rv) {
	php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_t *obj = PHP_<?php echo $uppername; ?>_<?php echo $entry['id']; ?>_OBJ(Z_OBJ_P(object));
	zend_string *member_str = zval_get_string(member);
	zval *retval;
	php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_prop_handler handler = NULL;

	if (obj->prop_handler != NULL) {
		handler = zend_hash_find_ptr(obj->prop_handler, member_str);
	}

	if (handler) {
		int ret = handler->read_func(obj, rv);
		if (ret == SUCCESS) {
			retval = rv;
		} else {
			retval = &EG(uninitialized_zval);
		}
	} else {
		zend_object_handlers *std_handler = zend_get_std_object_handlers();
		retval = std_handler->read_property(object, member, type, cache_slot, rv);
	}
	zend_string_release(member_str);
	return retval;
}

zval *php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_write_property(zval *object, zval *member, zval *value, void **cache_slot) {
	php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_t *obj = PHP_<?php echo $uppername; ?>_<?php echo $entry['id']; ?>_OBJ(Z_OBJ_P(object));
	zend_string *member_str = zval_get_string(member);
	php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_prop_handler handler = NULL;

	if (obj->prop_handler != NULL) {
		handler = zend_hash_find_ptr(obj->prop_handler, member_str);
	}

	if (handler) {
		handler->write_func(obj, value);
	} else {
		zend_object_handlers *std_handler = zend_get_std_object_handlers();
		retval = std_handler->write_property(object, member, value, cache_slot);
	}
	zend_string_release(member_str);
}

<?php
}
?>



ZEND_DECLARE_MODULE_GLOBALS(<?php echo $name; ?>)

PHP_MINFO_FUNCTION(<?php echo $name; ?>) {
    php_info_print_table_start();
    php_info_print_table_row(2, "<?php echo addslashes($name); ?> support", "Enabled");
    php_info_print_table_end();	
}

static PHP_MINIT_FUNCTION(<?php echo $name; ?>) {
	zend_class_entry ce;
<?php
foreach ($stringConstants as $constant) {
    echo "\t{$uppername}_G(string_constants)[" . $constant->idx . "] = zend_string_init(\"" . addslashes($constant->value) . "\", " . strlen($constant->value) . ", 1);\n";
}
?>

<?php
foreach ($classEntries as $entry) {
    if ($entry['ns']) {
        echo "\tINIT_NS_CLASS_ENTRY(ce, \"" . addslashes($entry['ns']) . "\", \"{$entry['name']}\", php_{$name}_{$entry['id']}_methods);\n";
    } else {
        echo "\tINIT_CLASS_ENTRY(ce, \"{$entry['name']}\", php_{$name}_{$entry['id']}_methods);\n";
    }
    echo "\tphp_{$name}_{$entry['id']}_ce = zend_register_internal_class(&ce);\n";
    echo "\tphp_{$name}_{$entry['id']}_ce->create_object = php_{$name}_{$entry['id']}_create;\n";
    echo "\tmemcpy(&php_{$name}_{$entry['id']}_handlers, zend_get_std_object_handlers(), sizeof(zend_object_handlers));\n";
    echo "\tphp_{$name}_{$entry['id']}_handlers->read_property = php_{$name}_{$entry['id']}_read_property;\n";
    echo "\tphp_{$name}_{$entry['id']}_handlers->write_property = php_{$name}_{$entry['id']}_write_property;\n";

    echo "\tzend_hash_init(&php_{$name}_{$entry['id']}_prop_handlers, 0, NULL, php_{$name}_{$entry['id']}_dtor_prop_handler, 1);\n";
    foreach ($entry['properties'] as $prop) {
        echo "\tphp_{$name}_{$entry['id']}_register_prop_handler(php_{$name}_{$entry['id']}_prop_handlers, \"" . addslashes($prop['name']) . "\", php_{$name}_{$entry['id']}_{$prop['name']}_read, php_{$name}_{$entry['id']}_{$prop['name']}_write);\n";
    }
}
?>
}


static PHP_MSHUTDOWN_FUNCTION(<?php echo $name; ?>) {
<?php
foreach ($stringConstants as $constant) {
    echo "\tzend_string_release({$uppername}_G(string_constants)[" . $constant->idx . "]);\n";
}
?>
}

static inline hashtable_release(HashTable* ht) {
	if (!--GC_REFCOUNT(ht)) {
		zend_hash_destroy(ht);
	}
}

<?php

echo implode("\n", $functionHeaders);

echo "\n\n";

echo implode("\n", $functions);

echo "\n\n";

foreach ($classEntries as $entry) {
    echo "static inline zend_object_value php_{$name}_{$entry['id']}_create(zend_class_entry *ce) {\n";
?>
	zend_object_value intern;
	php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_t *pobj = (php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_t*) ecalloc(1, sizeof(php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_t));

	zend_object_std_init(&pobj->std, ce);
	object_properties_init(&pobj->std, ce);

	intern.handle = zend_objects_store_put(
		pobj,
		zend_object_value php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_destroy,
		zend_object_value php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_free,
		NULL
	);

	intern.handlers = php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_handlers;
	return intern;
<?php
    echo "}\n\n";
    echo "static inline zend_object_value php_{$name}_{$entry['id']}_destroy(void *zobject, zend_object_handle handle) {\n";
?>
	php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_t *pobj = (php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_t*) zobject;
	zend_objects_destroy_object(zobject, handle);
<?php
    echo "}\n\n";

    echo "static inline zend_object_value php_{$name}_{$entry['id']}_free(void *zobject) {\n";
?>
	php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_t *pobj = (php_<?php echo $name; ?>_<?php echo $entry['id']; ?>_t*) zobject;
	zend_object_std_dtor(pobj->std);
	efree(pobj);
<?php
    echo "}\n\n";

    foreach ($entry['properties'] as $prop) {
        echo "int php_{$name}_{$entry['id']}_{$prop['name']}_read(php_{$name}_{$entry['id']}_t *obj, zval *retval) {\n";
        echo "\t" . $prop['typeInfo']['ztypeset']("retval", "obj->p_{$prop['name']}") . ";\n";
        echo "\treturn SUCCESS;\n";
        echo "}\n\n";
        echo "int php_{$name}_{$entry['id']}_{$prop['name']}_write(php_{$name}_{$entry['id']}_t *obj, zval *newval) {\n";
        echo "\tif (Z_TYPE_P(newval) != {$prop['typeInfo']['ztype']}) {\n";
        echo "\t\tzend_throw_error(NULL, \"Parameter is not an {$prop['typeInfo']['stringtype']}\");\n";
        echo "\t}\n";
        echo "\tobj->p_{$prop['name']} = {$prop['typeInfo']['ztypefetch']}(newval);\n";
        echo "\treturn SUCCESS;\n";
        echo "}\n\n";
    }
}

echo "\n\n";

echo implode("\n", $argInfo);

echo "\n\n";
?>

zend_function_entry <?php echo $name; ?>_functions[] = {
	<?php echo implode("\n\t", $functionEntry); ?>

	{NULL, NULL, NULL};
};


<?php
foreach ($classEntries as $entry) {
    echo "zend_function_entry *php_{$name}_{$entry['id']}_methods[] = {\n";
    foreach ($entry['methods'] as $method) {
        echo "\tPHP_ME({$entry['name']}, {$method['name']}, php_{$name}_{$entry['id']}_{$method['name']}_arginfo, ZEND_ACC_PUBLIC)\n";
    }
    echo "\t{NULL, NULL, NULL}\n";
    echo "};\n";
}
?>


zend_module_entry <?php echo $name; ?>_module_entry = {
	STANDARD_MODULE_HEADER,
	PHP_<?php echo $uppername; ?>_EXTNAME,
	<?php echo $name; ?>_functions,
	PHP_MINIT(<?php echo $name; ?>),
	PHP_MSHUTDOWN(<?php echo $name; ?>),
	NULL,
	NULL,
	PHP_MINFO(<?php echo $name; ?>),
	PHP_<?php echo $uppername; ?>_VERSION,
	STANDARD_MODULE_PROPERTIES
};

#ifdef COMPILE_DL_<?php echo $uppername; ?>

ZEND_GET_MODULE(<?php echo $name; ?>)
#endif
