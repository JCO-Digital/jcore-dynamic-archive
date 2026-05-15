const { readFileSync, writeFileSync } = require("fs");

exports.preCommit = (props) => {
	try {
		const pluginFileName = "jcore-dynamic-archive.php";
		const baseFile = readFileSync(pluginFileName);
		const baseString = baseFile
			.toString()
			.replace(/^(.*)Version:.*$/m, `$1Version: ${props.version}`);
		writeFileSync(pluginFileName, baseString);
		const readmeFileName = "readme.txt";
		const readmeFile = readFileSync(readmeFileName);
		const readmeString = readmeFile
			.toString()
			.replace(/^(.*)Stable tag:.*$/m, `$1Stable tag: ${props.version}`);
		writeFileSync(readmeFileName, readmeString);
	} catch (error) {
		console.error(error);
	}
};
