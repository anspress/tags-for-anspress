<?php
/**
 * Check if AnsPress is activate
 * @var AcceptanceTester
 */

$I = new AcceptanceTester($scenario);
$I->loginAsAdmin();

$I->wantTo('Check if AnsPress is installed');
$I->amOnPluginPage();
$I->seeInSource('AnsPress', 'CSS:tr#anspress .plugin-title>strong');
$I->wantTo('Check if AnsPress is active');
$I->seeElement('tr#anspress .plugin-title .deactivate');

$I->wantTo('Check if Categories for AnsPress is installed');
$I->amOnPluginPage();
$I->seeInSource('AnsPress', 'CSS:tr#tags-for-anspress .plugin-title>strong');
$I->wantTo('Check if Tags for AnsPress is active');
$I->seeElement('tr#tags-for-anspress .plugin-title .deactivate');

$I->wantTo('Add some tags');
$I->amOnPage( '/wp-admin/edit-tags.php?taxonomy=question_tag' );
$I->fillField('#tag-name', 'Awesome_Tag');
$I->click( '#addtag #submit' );
$I->waitForJS( 'return jQuery.active == 0;',60 );
$I->see( 'Awesome_Tag' );
