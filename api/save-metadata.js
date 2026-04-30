import { sql } from '@vercel/postgres';

export default async function handler(req, res) {
  if (req.method !== 'POST') return res.status(405).end();
  const { url, name, size, type } = req.body;
  const uuid = Math.random().toString(36).substring(2, 15);
  
  await sql`
    INSERT INTO files (uuid, user_id, original_name, stored_name, mime_type, file_size)
    VALUES (${uuid}, 1, ${name}, ${url}, ${type}, ${size})
  `;
  res.json({ success: true });
}
