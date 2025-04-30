// IMPORTANT: This file is included as a script in the HTML,
// so ending with `;` is required!
(function(){

	const params = {
		kcckey      : '__kcckey__',
		pidkey      : '__pidkey__',
		urlpatt     : '__urlpatt__',
		aclass      : '__aclass__',
		questSymbol : '__questSymbol__',
		ampSymbol   : '__ampSymbol__',
	};

	document.addEventListener( 'click', clickEventHandler );
	document.addEventListener( 'mousedown', clickEventHandler );
	document.addEventListener( 'contextmenu ', clickEventHandler );
	document.addEventListener( 'mouseover', hideUglyKccUrl );

	function hideUglyKccUrl( ev ){
		let a = ev.target;

		// test `a[href*={kcckey}]` selector
		if( 'A' !== a.tagName || a.href.indexOf( params.kcckey ) === -1 ){
			return;
		}

		let match = a.href.match( new RegExp( params.kcckey +'=(.+)' ) );
		let url = match[1] || '';
		if( ! url ){
			return;
		}

		if( parseInt( url ) ){
			url = '/#download' + url;
		}

		// !!! before a.href
		a.dataset.kccurl = a.href.replace( url, replaceExtraSymbols( url ) );
		a.href = url;
	}

	function clickEventHandler( ev ){

		let a = ev.target.closest( 'a' );
		if( ! a ){
			return;
		}

		if( a.dataset.kccurl ){
			a.href = a.dataset.kccurl;
			return;
		}

		let href = a.href;

		// modefied link
		if( href.indexOf( params.kcckey ) !== -1 ){
			let match = href.match( new RegExp( params.kcckey +'=(.*)' ) );
			if ( match && match[1] ) {
				let url = match[1];
				if( !! parseInt( url ) ){
					url = '/#download' + url;
				}

				a.dataset.kccurl = href.replace( url, replaceExtraSymbols( url ) );
			}
		}
		// count class
		else if( a.classList.contains( params.aclass ) ){
			a.dataset.kccurl = params.urlpatt
				.replace( '{in_post}',  (a.dataset[ params.pidkey ] || '') )
				.replace( '{download}', (a.dataset.kccdownload ? 1 : '') )
				.replace( '{url}', replaceExtraSymbols( href ) );
		}

		if( a.dataset.kccurl ){
			a.href = a.dataset.kccurl;
		}
	}

	function replaceExtraSymbols( url ){
		return url
			.replace( /[?]/g, params.questSymbol )
			.replace( /[&]/g, params.ampSymbol );
	}

})();
