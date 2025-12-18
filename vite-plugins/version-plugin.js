import fs from 'fs';
import path from 'path';

export default function versionPlugin() {
    return {
        name: 'version-plugin',
        closeBundle() {
            // Generate version info after build
            const versionInfo = {
                version: Date.now(),
                buildTime: new Date().toISOString(),
                hash: Math.random().toString(36).substring(7)
            };

            // Write to public directory so it's accessible via HTTP
            const publicDir = path.resolve(process.cwd(), 'public');
            const versionPath = path.join(publicDir, 'version.json');

            fs.writeFileSync(versionPath, JSON.stringify(versionInfo, null, 2));
            console.log('✅ Generated version.json:', versionInfo.version);
        }
    };
}

