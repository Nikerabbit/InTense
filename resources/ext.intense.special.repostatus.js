(function ( mw, $ ) {
	"use strict";

	function setupActions() {
		var
			$headers = $( '.intense-repoheader' ),
			iconRemove = mw.config.get( 'wgExtensionAssetsPath' ) + '/Translate/resources/images/remove.svg';

		$headers.each( function ( ) {
			$( this ).append(
				$( '<img>' ).prop( 'src', iconRemove )
			);
		} );

		$headers.on( 'click', 'img', function () {
			var id = $( this ).parent().data( 'intense' );
			(new mw.Api()).postWithToken( 'edit', {
				action: 'repomanage',
				subaction: 'delete',
				repoid: id
			} ).done( function () {
				location.reload();
			} );
		} );

	};

	$( document ).ready( setupActions );
}( mediaWiki, jQuery ) );
