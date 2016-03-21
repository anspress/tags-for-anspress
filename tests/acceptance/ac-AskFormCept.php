<?php
/**
 * AnsPress ask form
 */

$I = new AcceptanceTester($scenario );
$I->wantTo('Check AnsPress form error message' );
$I->switch_user('user1', 'user1' );
$I->amOnPage( '/questions/ask/' );
$I->seeElement('#ask_form' );

$I->click('#ask_form .ap-btn-submit' );

$I->waitForText('This field is required', 30 );
$I->wantTo('Submit new question' );
$I->fillField([ 'name' => 'title' ], $I->questions['question1'] );
$I->executeJS('jQuery("<input name=tags[] value=awesome_tag />").appendTo("#ask_form");');
$I->fillTinyMceEditorById('description', 'Fusce iaculis condimentum nisi, nec commodo eros molestie at. Nullam libero erat, sollicitudin eu condimentum sit amet, rhoncus ut lacus. Integer vulputate nibh et diam sagittis in dictum mauris dapibus. ' );

$I->click('#ask_form .ap-btn-submit' );
$I->waitForJS( 'return jQuery.active == 0;',60 );
$I->makeScreenshot('questions_page' );
$I->amOnPage( '/questions/' );
$I->see($I->questions['question1'] );


// Add a dummy comment in question 1.
$I->wantTo('Check comments' );
$I->switch_user('user2', 'user2' );
$I->amOnPage( '/questions/' );
$I->click( ['link' => $I->questions['question1']] );
$I->click( '.ap-q-cells .comment-btn' );
$I->waitForJS( 'return jQuery.active == 0;',60 );
$I->fillField([ 'name' => 'comment' ], $I->comment['comment1'] );
$I->click( '#ap-commentform input[name="submit"]' );
$I->waitForJS( 'return jQuery.active == 0;',60 );
$I->waitForText( $I->comment['comment1'], 60 );
$I->makeScreenshot('questions_comment' );

$I->wantTo('Check tags page' );
$I->amOnPage( '/questions/tags/' );
$I->see('Awesome_Tag');

$I->wantTo('Check tag page' );
$I->amOnPage( '/questions/tag/awesome_tag/' );
$I->see('Awesome_Tag');
