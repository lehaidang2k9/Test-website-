import { sql } from '@vercel/postgres';
import { del } from '@vercel/blob';

export default async function handler(req, res) {
  const { uuid } = req.query;
  const { rows } = await sql`SELECT stored_name FROM files WHERE uuid = ${uuid}`;
  
  if (rows.length > 0) {
    await del(rows[0].stored_name); // Xóa file trên mây
    await sql`DELETE FROM files WHERE uuid = ${uuid}`; // Xóa trong database
  }
  res.json({ success: true });
}
