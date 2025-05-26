const wordpressConfig = require( '@wordpress/scripts/config/webpack.config' );
const { resolve } = require( 'node:path' );

function extendSharedConfig( config ) {
	return {
		...config,
		resolve: {
			alias: {
				'@/shared': resolve( __dirname, 'shared' ),
			},
		},
	};
}

function extendScriptConfig( config ) {
	return {
		...config,
	};
}

function extendModuleConfig( config ) {
	return {
		...config,
		target: [ 'web' ],
	};
}

module.exports = ( () => {
	if ( Array.isArray( wordpressConfig ) ) {
		const [ scriptConfig, moduleConfig ] = wordpressConfig;

		const extendedScriptConfig = extendSharedConfig(
			extendScriptConfig( scriptConfig )
		);
		const extendedModuleConfig = extendSharedConfig(
			extendModuleConfig( moduleConfig )
		);

		return [ extendedScriptConfig, extendedModuleConfig ];
	} else {
		return extendSharedConfig( extendScriptConfig( wordpressConfig ) );
	}
} )();
