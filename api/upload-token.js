import { generateClientTokenFromReadWriteToken } from '@vercel/blob';

export default async function handler(req, res) {
  const token = await generateClientTokenFromReadWriteToken({
    returnPayload: JSON.stringify({ userId: 'haidang' }),
  });
  return res.json({ token });
}
