import { neon } from '@neondatabase/serverless';

export default async function handler(req, res) {
    const sql = neon(process.env.POSTGRES_URL);
    try {
        const files = await sql`SELECT * FROM files ORDER BY id DESC LIMIT 40`;
        const stats = await sql`SELECT COUNT(*) as count, SUM(file_size) as size FROM files`;
        res.status(200).json({ 
            files, 
            totalCount: parseInt(stats[0].count), 
            totalSize: parseInt(stats[0].size || 0) 
        });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
}
