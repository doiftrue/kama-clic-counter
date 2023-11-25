(function(){

	const params = {
		kcckey      : '{kcckey}',
		pidkey      : '{pidkey}',
		urlpatt     : '{urlpatt}',
		aclass      : '{aclass}',
		questSymbol : '{questSymbol}',
		ampSymbol   : '{ampSymbol}'
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

		let realurl  = a.href.match( new RegExp( params.kcckey +'=(.*)' ) )[1];

		if( realurl ){
			if( parseInt( realurl ) )
				realurl = '/#download'+ realurl;

			a.dataset.kccurl = a.href.replace( realurl, replaceUrlExtraSymbols(realurl) ); // before a.href !
			a.href = realurl;
		}
	}

	function replaceUrlExtraSymbols( url ){
		return url
			.replace( /[?]/g, params.questSymbol )
			.replace( /[&]/g, params.ampSymbol );
	}

	function clickEventHandler( ev ){
		let a = ev.target;

		if( 'A' !== ev.target.tagName ){
			return;
		}

		// already set before - `a[data-kccurl]`
		if( a.dataset.kccurl ){
			a.href = a.dataset.kccurl;
			return;
		}

		let href = a.href;

		// modified link
		if( href.indexOf( params.kcckey ) !== -1 ){
			let url = href.match( new RegExp( params.kcckey +'=(.*)' ) )[1];

			if( url ){
				if( !! parseInt(url) ){
					url = '/#download'+ url;
				}

				a.href = url;
				a.dataset.kccurl = href.replace( url, replaceUrlExtraSymbols( url ) );
			}
		}
		// count class
		else if( a.classList.contains( params.aclass ) ){
			let pid  = a.dataset[ params.pidkey ] || '';

			let kccurl;
			kccurl = params.urlpatt.replace( '{in_post}', pid );
			kccurl = kccurl.replace( '{download}', ( a.dataset.kccdownload ? 1 : '' ) );
			kccurl = kccurl.replace( '{url}', replaceUrlExtraSymbols( href ) );

			a.dataset.kccurl = kccurl;
		}

		a.dataset.kccurl && ( a.href = a.dataset.kccurl );
	}

})();
