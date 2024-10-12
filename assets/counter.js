(function(){

	const params = {
		kcckey      : '__kcckey__',
		pidkey      : '__pidkey__',
		urlpatt     : '__urlpatt__',
		aclass      : '__aclass__',
		questSymbol : '__questSymbol__',
		ampSymbol   : '__ampSymbol__'
	};

	document.addEventListener( 'click', clickEventHandler );
	document.addEventListener( 'mousedown', clickEventHandler );
	document.addEventListener( 'contextmenu ', clickEventHandler );
	document.addEventListener( 'mouseover', hideUglyKccUrl );

	function hideUglyKccUrl( ev ){
		let a = ev.target;

		// test `a[href*="'+ kcckey +'"]` selector
		if( 'A' !== a.tagName || a.href.indexOf( params.kcckey ) === -1 ){
			return;
		}

		let match = a.href.match( new RegExp( params.kcckey +'=(.*)' ) );
		if( match && match[1] ){
			let realurl = match[1];

			if( parseInt( realurl ) ){
				realurl = '/#download' + realurl;
			}

			// !!! before a.href
			a.dataset.kccurl = a.href.replace( realurl, replaceUrlExtraSymbols( realurl ) );
			a.href = realurl;
		}
	}

	function replaceUrlExtraSymbols( url ){
		return url
			.replace( /[?]/g, params.questSymbol )
			.replace( /[&]/g, params.ampSymbol );
	}

	function clickEventHandler( ev ){
		let a = ev.target.closest( `.${params.aclass}` );

		if( !a || 'A' !== a.tagName ){
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

				a.href = url;
				a.dataset.kccurl = href.replace( url, replaceUrlExtraSymbols( url ) );
			}
		}
		// count class
		else if( a.classList.contains( params.aclass ) ){
			let pid  = a.dataset[ params.pidkey ] || '';

			let kccurl = params.urlpatt.replace( '{in_post}', pid )
				.replace( '{download}', ( a.dataset.kccdownload ? 1 : '' ) )
				.replace( '{url}', replaceUrlExtraSymbols( href ) );

			a.dataset.kccurl = kccurl;
		}

		a.dataset.kccurl && ( a.href = a.dataset.kccurl );
	}

})();
