<?php

/**
 * 貼到 routes/backend.php 的 auth:backend middleware 群組內（與 c150402 相同位置）。
 */

Route::get('list-preferences/{pageKey}', 'AdminUserListPreferenceController@show')->name('list_preferences.show');
Route::put('list-preferences/{pageKey}', 'AdminUserListPreferenceController@update')->name('list_preferences.update');
Route::delete('list-preferences/{pageKey}', 'AdminUserListPreferenceController@destroy')->name('list_preferences.destroy');
