/**
 * Travelpont Úticélok – Admin: szint-függő mezők mutatása/rejtése
 *
 * A "Szint" (#tpu_szint) választó értéke alapján csak az adott szinthez
 * releváns mezők (data-szint attribútum) látszanak. Ha egy meta-doboz minden
 * mezője elrejtésre kerül, maga a doboz is elrejtődik.
 *
 * Ha nincs kiválasztott szint (üres érték), minden mező látszik – így semmi
 * nem tűnik el váratlanul, mielőtt a szerkesztő döntött volna.
 */
( function () {
	'use strict';

	function frissit() {
		var select = document.getElementById( 'tpu_szint' );
		if ( ! select ) {
			return;
		}
		var szint = select.value; // '', 'orszag', 'regio', 'varos', 'egyeb'

		var mezok = document.querySelectorAll( '.tpu-field[data-szint]' );
		mezok.forEach( function ( mezo ) {
			var szintek = ( mezo.getAttribute( 'data-szint' ) || '' ).split( /\s+/ );
			// Üres kiválasztásnál mindent mutatunk; egyébként csak az egyezőt.
			var lathato = szint === '' || szintek.indexOf( szint ) !== -1;
			mezo.style.display = lathato ? '' : 'none';
		} );

		// Üressé vált meta-dobozok elrejtése (csak azok, amelyekben KIZÁRÓLAG
		// szint-függő mezők vannak – az általános dobozok mindig maradnak).
		var dobozok = document.querySelectorAll( '.postbox' );
		dobozok.forEach( function ( doboz ) {
			var osszesMezo = doboz.querySelectorAll( '.tpu-field' );
			if ( osszesMezo.length === 0 ) {
				return;
			}
			var vanLathato = false;
			osszesMezo.forEach( function ( mezo ) {
				if ( mezo.style.display !== 'none' ) {
					vanLathato = true;
				}
			} );
			doboz.style.display = vanLathato ? '' : 'none';
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		var select = document.getElementById( 'tpu_szint' );
		if ( ! select ) {
			return;
		}
		select.addEventListener( 'change', frissit );
		frissit();
	} );
} )();
