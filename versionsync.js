const { readFileSync, writeFileSync } = require('fs');

exports.preCommit = (props) => {
	try {
		const pluginFileName = 'jcore-dynamic-archive.php';
		const baseFile = readFileSync(pluginFileName);
		const baseString = baseFile
			.toString()
			.replace(/^(.*)Version:.*$/m, `$1Version: ${props.version}`);
		writeFileSync(pluginFileName, baseString);
	} catch (error) {
		console.error(error);
	}
};
