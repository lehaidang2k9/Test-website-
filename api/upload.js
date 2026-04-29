import { put } from '@vercel/blob';
import { neon } from '@neondatabase/serverless';

export default async function handler(req, res) {
    if (req.method !== 'POST') return res.status(405).json({ error: 'Method not allowed' });

    const sql = neon(process.env.POSTGRES_URL);
    const filename = req.query.filename;

    try {
        // Tải lên Vercel Blob
        const blob = await put(filename, req, {
            access: 'public',
            contentType: req.headers['content-type'],
        });

        // Lưu thông tin vào Database
        await sql`
            INSERT INTO files (original_name, mime_type, file_size, url) 
            VALUES (${filename}, ${req.headers['content-type']}, ${req.headers['content-length']}, ${blob.url})
        `;

        return res.status(200).json(blob);
    } catch (error) {
        return res.status(500).json({ error: error.message });
    }
}

export const config = { api: { bodyParser: false } };
