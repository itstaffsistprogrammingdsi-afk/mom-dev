import { Server } from '@hocuspocus/server';
import { createHmac, timingSafeEqual } from 'node:crypto';
import { existsSync } from 'node:fs';
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import * as Y from 'yjs';

const baseDir = dirname(fileURLToPath(import.meta.url));
const envPath = join(baseDir, '..', '.env');

if (existsSync(envPath)) {
    try {
        process.loadEnvFile(envPath);
    } catch {
        // Environment variables from the host take precedence.
    }
}

const storageDir = process.env.COLLAB_STORAGE_DIR ?? join(baseDir, 'storage');
const collabSecret = process.env.COLLAB_SECRET ?? 'change-me-in-production';

function decodeBase64Url(value) {
    return Buffer.from(value.replace(/-/g, '+').replace(/_/g, '/'), 'base64').toString('utf8');
}

function verifyToken(token, documentName) {
    if (typeof token !== 'string' || !token.includes('.')) {
        throw new Error('Token kolaborasi tidak valid.');
    }

    const [encoded, signature] = token.split('.');
    const expected = createHmac('sha256', collabSecret).update(encoded).digest('hex');

    const signatureBuffer = Buffer.from(signature, 'utf8');
    const expectedBuffer = Buffer.from(expected, 'utf8');

    if (signatureBuffer.length !== expectedBuffer.length || !timingSafeEqual(signatureBuffer, expectedBuffer)) {
        throw new Error('Tanda tangan token kolaborasi tidak valid.');
    }

    const payload = JSON.parse(decodeBase64Url(encoded));

    if (Number(payload.exp) * 1000 < Date.now()) {
        throw new Error('Token kolaborasi sudah kedaluwarsa.');
    }

    if (`meeting-${payload.mid}` !== documentName) {
        throw new Error('Token tidak sesuai dengan dokumen.');
    }

    return payload;
}

function documentPath(documentName) {
    const safeName = documentName.replace(/[^a-zA-Z0-9_-]/g, '_');

    return join(storageDir, `${safeName}.bin`);
}

const server = Server.configure({
    port: Number(process.env.COLLAB_PORT ?? 1234),
    address: process.env.COLLAB_HOST ?? '127.0.0.1',
    async onAuthenticate({ token, documentName }) {
        const user = verifyToken(token, documentName);

        return {
            user: {
                id: user.uid,
                name: user.name,
                canEdit: Boolean(user.edit),
            },
        };
    },
    async onLoadDocument({ documentName }) {
        const document = new Y.Doc();

        try {
            const stored = await readFile(documentPath(documentName));
            Y.applyUpdate(document, new Uint8Array(stored));
        } catch {
            return document;
        }

        return document;
    },
    async onStoreDocument({ documentName, document }) {
        await mkdir(storageDir, { recursive: true });
        await writeFile(documentPath(documentName), Buffer.from(Y.encodeStateAsUpdate(document)));
    },
});

await server.listen();

console.log(`Hocuspocus listening on ws://localhost:${server.configuration.port}`);
