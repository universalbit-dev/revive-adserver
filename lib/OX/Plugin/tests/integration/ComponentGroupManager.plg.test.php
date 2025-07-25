<?php

/*
+---------------------------------------------------------------------------+
| Revive Adserver                                                           |
| http://www.revive-adserver.com                                            |
|                                                                           |
| Copyright: See the COPYRIGHT.txt file.                                    |
| License: GPLv2 or later, see the LICENSE.txt file.                        |
+---------------------------------------------------------------------------+
*/

require_once LIB_PATH . '/Plugin/ComponentGroupManager.php';

/**
 * A class for testing the Test_OX_Plugin_ComponentGroupManager class.
 *
 * @package Plugins
 * @subpackage TestSuite
 */
class Test_OX_Plugin_ComponentGroupManager extends UnitTestCase
{
    public $testpathData = '/lib/OX/Plugin/tests/data/';
    public $testpathPackages = '/lib/OX/Plugin/tests/data/plugins/etc/';
    public $testpathPluginsAdmin = '/lib/OX/Plugin/tests/data/www/admin/plugins/';


    public function test__checkOpenXCompatibility()
    {
        $oPluginManager = new OX_Plugin_ComponentGroupManager();

        $aPass[] = '2.4.0-dev';
        $aPass[] = '2.4.0-alpha';
        $aPass[] = '2.4.0-beta';
        $aPass[] = '2.4.0';
        $aPass[] = '2.4.1-dev';
        $aPass[] = '2.4.1-alpha';
        $aPass[] = '2.4.1-beta-rc1';
        $aPass[] = '2.4.1-beta-rc2';
        $aPass[] = '2.4.1-beta';
        $aPass[] = '2.4.1';
        $aPass[] = '2.5.0';
        $aPass[] = '2.5.5';
        $aPass[] = '2.5.50-dev';
        $aPass[] = '2.5.50-beta-rc1';
        $aPass[] = '2.5.50';
        $aPass[] = '5.5.3';
        $aPass[] = VERSION;
        foreach ($aPass as $version) {
            $this->assertTrue($oPluginManager->_checkOpenXCompatibility('testPlugin', $version));
        }

        $aFail[] = '6.8.0';
        $aFail[] = '6.8.1-dev';
        $aFail[] = '6.8.1-alpha';
        $aFail[] = '6.8.1-beta-rc1';
        $aFail[] = '6.8.1-beta-rc2';
        $aFail[] = '6.8.1-beta';
        $aFail[] = '6.8.1-RC1';
        $aFail[] = '6.8.1';
        $aFail[] = '6.9.5';
        $aFail[] = '6.9.50-dev';
        $aFail[] = '6.9.50-beta-rc1';
        $aFail[] = '6.9.50';
        foreach ($aFail as $version) {
            $this->assertFalse($oPluginManager->_checkOpenXCompatibility('testPlugin', $version));
        }
    }

    public function test_registerPreferences()
    {
        $aPreferences[0] = [
            'name' => 'testpref',
            'type' => 'text',
            'label' => 'Test Pref',
            'required' => '1',
            'size' => 12,
            'visible' => 1,
            'permission' => 'ADMIN',
            'value' => 'testval',
        ];

        $oPluginManager = new OX_Plugin_ComponentGroupManager();
        $oPluginManager->_registerPreferences('testPlugin', $aPreferences);
        $doPreferences = OA_Dal::factoryDO('preferences');
        $doPreferences->preference_name = 'testPlugin_testpref';
        $this->assertTrue($doPreferences->find());
        $oPluginManager->_unregisterPreferences('testPlugin', $aPreferences);
        $this->assertFalse($doPreferences->find());
    }

    public function test_registerSettings()
    {
        $aSettings[0] = [
            'key' => 'testset1',
            'type' => 'text',
            'label' => 'Test Setting',
            'required' => '1',
            'size' => 12,
            'visible' => 1,
            'value' => 'testval1',
        ];

        $oPluginManager = new OX_Plugin_ComponentGroupManager();
        $oPluginManager->_registerSettings('testPlugin', $aSettings);
        $aConf = &$GLOBALS['_MAX']['CONF'];
        $this->assertTrue(isset($aConf['testPlugin']));
        $this->assertTrue(isset($aConf['testPlugin']['testset1']));
        $this->assertEqual($aConf['testPlugin']['testset1'], 'testval1');
        $oPluginManager->_unregisterSettings('testPlugin');
        $this->assertFalse(isset($aConf['testPlugin']));
    }

    public function test_checkMenus()
    {
        OA_Admin_Menu::_clearCache(OA_ACCOUNT_ADMIN);
        $oPluginManager = new OX_Plugin_ComponentGroupManager();

        $aMenus[OA_ACCOUNT_ADMIN] = [
            0 => [
                'insertafter' => "999",
            ],
        ];
        $this->assertFalse($oPluginManager->_checkMenus('testPlugin', $aMenus));

        $aMenus[OA_ACCOUNT_ADMIN] = [
            0 => [
                'insertbefore' => "999",
            ],
        ];
        $this->assertFalse($oPluginManager->_checkMenus('testPlugin', $aMenus));
        $aMenus[OA_ACCOUNT_ADMIN] = [
            0 => [
                'addto' => "999",
            ],
        ];
        $this->assertFalse($oPluginManager->_checkMenus('testPlugin', $aMenus));

        $aMenus[OA_ACCOUNT_ADMIN] = [
            0 => [
                'add' => "test-plugin-root",
                'link' => "plugins/testPlugin/testPlugin.php",
                'value' => 'Test Plugin',
            ],
            1 => [
                'addto' => "test-plugin-root",
                'index' => "test-plugin-1",
                'link' => "plugins/testPlugin/testPlugin-page.php?action=1",
                'value' => 'Test Menu 1',
            ],
            2 => [
                'insertafter' => "test-plugin-1",
                'index' => "test-plugin-3",
                'link' => "plugins/testPlugin/testPlugin.php?action=3",
                'value' => 'Test Menu 3',
            ],
            3 => [
                'insertbefore' => "test-plugin-3",
                'index' => "test-plugin-2",
                'link' => "plugins/testPlugin/testPlugin.php?action=2",
                'value' => 'Test Menu 2',
            ],
        ];
        $oMenu = $oPluginManager->_checkMenus('testPlugin', $aMenus);
        $this->assertIsA($oMenu, 'OA_Admin_Menu');

        $oSection = $oMenu->get('test-plugin-root', false);
        $this->assertIsA($oSection, 'OA_Admin_Menu_Section');
        $this->assertEqual(count($oSection->aSections), 3);
        $this->assertEqual($oSection->aSections[0]->id, 'test-plugin-1');
        $this->assertEqual($oSection->aSections[1]->id, 'test-plugin-2');
        $this->assertEqual($oSection->aSections[2]->id, 'test-plugin-3');

        $oSection1 = $oMenu->get('test-plugin-1', false);
        $this->assertIsA($oSection1, 'OA_Admin_Menu_Section');
        $this->assertEqual($oSection->aSections[0]->id, $oSection1->id);

        $oSection2 = $oMenu->get('test-plugin-2', false);
        $this->assertIsA($oSection2, 'OA_Admin_Menu_Section');
        $this->assertEqual($oSection->aSections[1]->id, $oSection2->id);

        $oSection3 = $oMenu->get('test-plugin-3', false);
        $this->assertIsA($oSection3, 'OA_Admin_Menu_Section');
        $this->assertEqual($oSection->aSections[2]->id, $oSection3->id);

        $this->assertTrue($oMenu->_saveToCache(OA_ACCOUNT_ADMIN));
        $oMenu = OA_Admin_Menu::_loadFromCache(OA_ACCOUNT_ADMIN);

        $this->assertIsA($oMenu, 'OA_Admin_Menu');

        $oSection = $oMenu->get('test-plugin-root', false);
        $this->assertIsA($oSection, 'OA_Admin_Menu_Section');
        $this->assertEqual(count($oSection->aSections), 3);
        $this->assertEqual($oSection->aSections[0]->id, 'test-plugin-1');
        $this->assertEqual($oSection->aSections[1]->id, 'test-plugin-2');
        $this->assertEqual($oSection->aSections[2]->id, 'test-plugin-3');

        $oSection1 = $oMenu->get('test-plugin-1', false);
        $this->assertIsA($oSection1, 'OA_Admin_Menu_Section');
        $this->assertEqual($oSection->aSections[0]->id, $oSection1->id);

        $oSection2 = $oMenu->get('test-plugin-2', false);
        $this->assertIsA($oSection2, 'OA_Admin_Menu_Section');
        $this->assertEqual($oSection->aSections[1]->id, $oSection2->id);

        $oSection3 = $oMenu->get('test-plugin-3', false);
        $this->assertIsA($oSection3, 'OA_Admin_Menu_Section');
        $this->assertEqual($oSection->aSections[2]->id, $oSection3->id);
    }

    public function test_mergeMenu()
    {
        $oPluginManager = new OX_Plugin_ComponentGroupManager();
        $oPluginManager->pathPackages = $this->testpathPackages;
        $oMenu = new OA_Admin_Menu();

        $GLOBALS['_MAX']['CONF']['pluginGroupComponents'] = ['testPlugin' => 1];

        $oPluginManager->mergeMenu($oMenu, OA_ACCOUNT_ADMIN);
        $this->assertEqual(count($oMenu->aAllSections), 3);
        $this->assertTrue(array_key_exists('test-menu-admin', $oMenu->aAllSections));
        $this->assertTrue(array_key_exists('test-menu-admin-1', $oMenu->aAllSections));
        $this->assertTrue(array_key_exists('test-menu-admin-2', $oMenu->aAllSections));

        $oPluginManager->mergeMenu($oMenu, OA_ACCOUNT_MANAGER);
        $this->assertEqual(count($oMenu->aAllSections), 5);
        $this->assertTrue(array_key_exists('test-menu-mgr', $oMenu->aAllSections));
        $this->assertTrue(array_key_exists('test-menu-mgr-1', $oMenu->aAllSections));

        TestEnv::restoreConfig();
    }

    public function test_Tables()
    {
        $prefix = $GLOBALS['_MAX']['CONF']['table']['prefix'];
        $oTable = new OA_DB_Table();
        $oTable->init(MAX_PATH . $this->testpathPackages . 'testPlugin/etc/tables_testplugin.xml');
        $version = $oTable->aDefinition['version'];
        $oPluginManager = new OX_Plugin_ComponentGroupManager();
        $oPluginManager->pathPackages = $this->testpathPackages;
        $aSchema = [
            'mdb2schema' => 'tables_testplugin',
        ];
        $aDbTables = OA_DB_Table::listOATablesCaseSensitive('testplugin_table');
        $this->assertEqual(count($aDbTables), 0);

        $this->assertEqual($oPluginManager->_createTables('testPlugin', $aSchema), $version);

        $aDbTables = OA_DB_Table::listOATablesCaseSensitive('testplugin_table');
        $this->assertEqual(count($aDbTables), 1);
        $this->assertEqual($aDbTables[0], $prefix . 'testplugin_table');

        $this->assertTrue($oPluginManager->_dropTables('testPlugin', $aSchema));

        $aDbTables = OA_DB_Table::listOATablesCaseSensitive('testplugin_table');
        $this->assertEqual(count($aDbTables), 0);
    }

    public function test_cacheDataObjects()
    {
        $oPluginManager = new OX_Plugin_ComponentGroupManager();
        $oPluginManager->pathPackages = $this->testpathPackages;
        $pathPlugin = MAX_PATH . $this->testpathPackages . 'testPlugin/etc/DataObjects/';
        $aSchemaPlugin = @parse_ini_file($pathPlugin . 'db_schema.ini', true);
        $aLinksPlugin = @parse_ini_file($pathPlugin . 'db_schema.links.ini', true);
        $outputDir = MAX_PATH . '/var/';

        // Test 1 - re-create cache
        $aConf = ['testPlugin' => 1, 'testDepends' => 0];
        $GLOBALS['_MAX']['CONF']['pluginGroupComponents'] = $aConf;

        $aResult = $oPluginManager->_cacheDataObjects(null, null, $outputDir);
        $aSchemas = $aResult['schemas']->toArray();
        $aLinks = $aResult['links']->toArray();

        $this->assertEqual(count($aSchemaPlugin), count($aSchemas['root']));
        $this->assertEqual(count($aLinksPlugin), count($aLinks['root']));

        $this->assertTrue(file_exists($outputDir . 'db_schema.ini'));
        $this->assertTrue(file_exists($outputDir . 'db_schema.links.ini'));
        @unlink($outputDir . 'db_schema.ini');
        @unlink($outputDir . 'db_schema.links.ini');
        $GLOBALS['_MAX']['CONF']['pluginGroupComponents'] = [];

        // Test 2 - re-create cache with a new plugin, schema not provided
        $aResult = $oPluginManager->_cacheDataObjects('testPlugin', null, $outputDir);
        $aSchemas = $aResult['schemas']->toArray();
        $aLinks = $aResult['links']->toArray();

        $this->assertEqual(count($aSchemaPlugin), count($aSchemas['root']));
        $this->assertEqual(count($aLinksPlugin), count($aLinks['root']));

        $this->assertTrue(file_exists($outputDir . 'db_schema.ini'));
        $this->assertTrue(file_exists($outputDir . 'db_schema.links.ini'));
        @unlink($outputDir . 'db_schema.ini');
        @unlink($outputDir . 'db_schema.links.ini');

        // Test 2 - re-create cache with a new plugin, schema provided
        $aSchema = [
            'mdb2schema' => 'tables_testPlugin',
            'dboschema' => 'db_schema',
            'dbolinks' => 'db_schema.links',
            'dataobjects' => [0 => 'Testplugin_table.php'],
        ];
        $aResult = $oPluginManager->_cacheDataObjects('testPlugin', $aSchema, $outputDir);
        $aSchemas = $aResult['schemas']->toArray();
        $aLinks = $aResult['links']->toArray();

        $this->assertEqual(count($aSchemaPlugin), count($aSchemas['root']));
        $this->assertEqual(count($aLinksPlugin), count($aLinks['root']));

        $this->assertTrue(file_exists($outputDir . 'db_schema.ini'));
        $this->assertTrue(file_exists($outputDir . 'db_schema.links.ini'));
        @unlink($outputDir . 'db_schema.ini');
        @unlink($outputDir . 'db_schema.links.ini');
    }

    /**
     * @todo write test
     *
     */
    public function test_getDataObjectSchema() {}

    public function test_putDataObjects()
    {
        $oPluginManager = new OX_Plugin_ComponentGroupManager();
        $oPluginManager->pathPackages = $this->testpathPackages;
        $outputDir = MAX_PATH . '/var/';

        $aSchema = [
            'mdb2schema' => 'tables_testPlugin',
            'dataobjects' => [0 => 'Testplugin_table.php'],
        ];
        $oPluginManager->_putDataObjects('testPlugin', $aSchema, $outputDir);
        $this->assertTrue(file_exists($outputDir . 'Testplugin_table.php'));
        @unlink($outputDir . 'Testplugin_table.php');
    }

    public function test_removeFiles()
    {
        $name = 'testPlugin';
        $varPath = '/var/tmp';
        if (!file_exists(MAX_PATH . $varPath)) {
            mkdir(MAX_PATH . $varPath);
        }
        // set up some package folders and files
        $aConf = $GLOBALS['_MAX']['CONF']['pluginPaths'];

        $aPathPlugins = explode('/', $aConf['plugins']);
        $varPathPlugins = $varPath;
        foreach ($aPathPlugins as $sub) {
            if (trim($sub)) {
                $varPathPlugins .= '/' . $sub;
                if (!file_exists(MAX_PATH . $varPathPlugins)) {
                    mkdir(MAX_PATH . $varPathPlugins);
                }
            }
        }
        $varPathPlugins .= '/';
        $aPathPackages = explode('/', $aConf['packages']);
        $varPathPackages = $varPath;
        foreach ($aPathPackages as $sub) {
            if (trim($sub)) {
                $varPathPackages .= '/' . $sub;
                if (!file_exists(MAX_PATH . $varPathPackages)) {
                    mkdir(MAX_PATH . $varPathPackages);
                }
            }
        }
        $varPathPackages .= '/';
        if (!file_exists(MAX_PATH . $varPathPackages . $name)) {
            mkdir(MAX_PATH . $varPathPackages . $name);
            mkdir(MAX_PATH . $varPathPackages . $name . '/etc');
        }
        $file = 'testPluginPackage.xml';
        copy(MAX_PATH . $this->testpathPackages . $file, MAX_PATH . $varPathPackages . $file);
        $this->assertTrue(file_exists(MAX_PATH . $varPathPackages . $file), 'not found ' . $file);

        $file = $name . '/testPlugin.xml';
        copy(MAX_PATH . $this->testpathPackages . $file, MAX_PATH . $varPathPackages . $file);
        $this->assertTrue(file_exists(MAX_PATH . $varPathPackages . $file), 'not found ' . $file);

        $file = $name . '/processPreferences.php';
        copy(MAX_PATH . $this->testpathPackages . $file, MAX_PATH . $varPathPackages . $file);
        $this->assertTrue(file_exists(MAX_PATH . $varPathPackages . $file), 'not found ' . $file);

        $file = 'testPluginPackage.readme.txt';
        copy(MAX_PATH . $this->testpathPackages . $file, MAX_PATH . $varPathPackages . $file);
        $this->assertTrue(file_exists(MAX_PATH . $varPathPackages . $file), 'not found ' . $file);

        $file = $name . '/etc/tables_testplugin.xml';
        copy(MAX_PATH . $this->testpathPackages . $file, MAX_PATH . $varPathPackages . $file);
        $this->assertTrue(file_exists(MAX_PATH . $varPathPackages . $file), 'not found ' . $file);

        // set up some admin folders and files
        $aPathAdmin = explode('/', $aConf['admin']);
        $varPathAdmin = $varPath;
        foreach ($aPathAdmin as $sub) {
            if (trim($sub)) {
                $varPathAdmin .= '/' . $sub;
                if (!file_exists(MAX_PATH . $varPathAdmin)) {
                    mkdir(MAX_PATH . $varPathAdmin);
                }
            }
        }
        $varPathAdmin .= '/';
        if (!file_exists(MAX_PATH . $varPathAdmin . $name)) {
            mkdir(MAX_PATH . $varPathAdmin . $name);
            mkdir(MAX_PATH . $varPathAdmin . $name . '/templates');
        }
        $file = $name . '/testPlugin-index.php';
        copy(MAX_PATH . $this->testpathPluginsAdmin . $file, MAX_PATH . $varPathAdmin . $file);
        $this->assertTrue(file_exists(MAX_PATH . $varPathAdmin . $file), 'not found ' . $file);

        $file = $name . '/templates/testPlugin.html';
        copy(MAX_PATH . $this->testpathPluginsAdmin . $file, MAX_PATH . $varPathAdmin . $file);
        $this->assertTrue(file_exists(MAX_PATH . $varPathAdmin . $file), 'not found ' . $file);

        // finally finished setting up the test data!

        $oPluginManager = new OX_Plugin_ComponentGroupManager();
        $oPluginManager->pathPackages = $varPathPackages;
        $oPluginManager->pathPluginsAdmin = $varPathAdmin;

        // check the package file
        $oPluginManager->aErrors = [];
        $aPkgFiles[] = ['path' => OX_PLUGIN_PLUGINPATH . '/', 'name' => 'testPluginPackage.xml'];
        $this->assertTrue($oPluginManager->_checkFiles('', $aPkgFiles));
        if ($oPluginManager->countErrors()) {
            foreach ($oPluginManager->aErrors as $msg) {
                $this->assertTrue(false, $msg);
            }
        }

        // assuming its all ok, remove the package file (send in no name)
        // usually used for package readme files
        $oPluginManager->aErrors = [];
        //$this->assertTrue($oPluginManager->_removeFiles('', array('files'=>$aPkgFiles)));
        $this->assertTrue($oPluginManager->_removeFiles('', $aPkgFiles));
        if ($oPluginManager->countErrors()) {
            foreach ($oPluginManager->aErrors as $msg) {
                $this->assertTrue(false, $msg);
            }
        }

        // check again, this time it should be gone
        $oPluginManager->aErrors = [];
        $this->assertFalse($oPluginManager->_checkFiles('', $aPkgFiles));
        $this->assertEqual($oPluginManager->countErrors(), 1);


        // check plugin files
        $oPluginManager->aErrors = [];
        $aFiles = [];
        $aFiles[] = ['path' => OX_PLUGIN_ADMINPATH . '/templates/', 'name' => 'testPlugin.html'];
        $aFiles[] = ['path' => OX_PLUGIN_ADMINPATH . '/', 'name' => 'testPlugin-index.php'];
        $aFiles[] = ['path' => OX_PLUGIN_GROUPPATH . '/etc/', 'name' => 'tables_testplugin.xml'];
        $aFiles[] = ['path' => OX_PLUGIN_GROUPPATH . '/', 'name' => 'testPlugin.xml'];
        $aFiles[] = ['path' => OX_PLUGIN_GROUPPATH . '/', 'name' => 'processPreferences.php'];
        $aFiles[] = ['path' => OX_PLUGIN_GROUPPATH . '/etc/', 'name' => 'tables_testplugin.xml'];
        $aFiles[] = ['path' => OX_PLUGIN_PLUGINPATH . '/', 'name' => 'testPluginPackage.readme.txt'];

        $this->assertTrue($oPluginManager->_checkFiles($name, $aFiles));


        if ($oPluginManager->countErrors()) {
            foreach ($oPluginManager->aErrors as $msg) {
                $this->assertTrue(false, $msg);
            }
        }

        // remove the plugin files
        $oPluginManager->aErrors = [];
        //$this->assertTrue($oPluginManager->_removeFiles($name, array('files'=>$aFiles)));
        $this->assertTrue($oPluginManager->_removeFiles($name, $aFiles));
        if ($oPluginManager->countErrors()) {
            foreach ($oPluginManager->aErrors as $msg) {
                $this->assertTrue(false, $msg);
            }
        }
        // check again, this time they should be gone
        $oPluginManager->aErrors = [];
        $this->assertFalse($oPluginManager->_checkFiles($name, $aFiles));
        $this->assertEqual($oPluginManager->countErrors(), 1);

        // _removeFiles will also remove the specific plugin folders if it is empty
        $this->assertFalse(file_exists(MAX_PATH . $varPathPackages . $name . '/etc'));
        $this->assertFalse(file_exists(MAX_PATH . $varPathPackages . $name));
        $this->assertFalse(file_exists(MAX_PATH . $varPathAdmin . $name . '/templates'));
        $this->assertFalse(file_exists(MAX_PATH . $varPathAdmin . $name));
        // not the top level ones though
        $this->assertTrue(file_exists(MAX_PATH . $varPathPackages));
        $this->assertTrue(file_exists(MAX_PATH . $varPathAdmin));

        @rmdir(MAX_PATH . $varPathPackages);
        @rmdir(MAX_PATH . $varPathAdmin);

        @rmdir(MAX_PATH . $varPath . 'www/admin');
        @rmdir(MAX_PATH . $varPath . 'www');
        @rmdir(MAX_PATH . $varPath . 'plugins');
        @rmdir(MAX_PATH . $varPath);
    }

    public function test_removeDataObjects()
    {
        $oPluginManager = new OX_Plugin_ComponentGroupManager();
        $oPluginManager->pathPackages = $this->testpathPackages;
        $outputDir = MAX_PATH . '/var/';

        $aSchema = [
            'mdb2schema' => 'tables_testPlugin',
            'dataobjects' => [0 => 'Testplugin_table.php'],
        ];
        copy(MAX_PATH . $this->testpathPackages . 'testPlugin/etc/DataObjects/Testplugin_table.php', $outputDir . 'Testplugin_table.php');
        $this->assertTrue(file_exists($outputDir . 'Testplugin_table.php'));
        $oPluginManager->_removeDataObjects('testPlugin', $aSchema, $outputDir);
        $this->assertFalse(file_exists($outputDir . 'Testplugin_table.php'));
    }

    public function test_DependencyArray()
    {
        $oPluginManager = new OX_Plugin_ComponentGroupManager();
        $oPluginManager->pathPackages = $this->testpathPackages;

        $aConf = ['testPlugin' => 1, 'testDepends' => 0];
        $GLOBALS['_MAX']['CONF']['pluginGroupComponents'] = $aConf;

        $oPluginManager->_registerPluginVersion('testPlugin', '0.0.1');
        $oPluginManager->_registerPluginVersion('testDepends', '0.0.1-test');

        $aDepends = $oPluginManager->_buildDependencyArray();

        $this->assertIsA($aDepends, 'array');
        $this->assertEqual(count($aDepends), 2);

        $this->assertEqual($aDepends['testPlugin']['isDependedOnBy'][0], 'testDepends');
        $this->assertEqual($aDepends['testDepends']['dependsOn']['testPlugin'], '0.0.1');

        $this->assertTrue($oPluginManager->_saveDependencyArray($aDepends));

        $aDepends = $oPluginManager->_loadDependencyArray();

        $this->assertIsA($aDepends, 'array');
        $this->assertEqual(count($aDepends), 2);

        $this->assertTrue($oPluginManager->_hasDependencies('testPlugin'));
        $this->assertFalse($oPluginManager->_hasDependencies('testDepends'));


        $this->assertEqual($aDepends['testPlugin']['isDependedOnBy'][0], 'testDepends');
        $this->assertEqual($aDepends['testDepends']['dependsOn']['testPlugin'], '0.0.1');

        $GLOBALS['_MAX']['CONF']['pluginGroupComponents'] = [];

        $aDepends = $oPluginManager->_buildDependencyArray();
        $this->assertIsA($aDepends, 'array');
        $this->assertEqual(count($aDepends), 0);

        $this->assertTrue($oPluginManager->_saveDependencyArray($aDepends));

        $aDepends = $oPluginManager->_loadDependencyArray();
        $this->assertIsA($aDepends, 'array');
        $this->assertEqual(count($aDepends), 0);

        $oPluginManager->_clearDependencyCache();

        $oPluginManager->_unregisterPluginVersion('testPlugin');
        $oPluginManager->_unregisterPluginVersion('testDepends');

        unset($GLOBALS['_MAX']['CONF']['pluginGroupComponents']);
    }
}
