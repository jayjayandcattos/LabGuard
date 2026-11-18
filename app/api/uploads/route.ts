import { randomUUID } from "node:crypto";
import { mkdir, writeFile } from "node:fs/promises";
import path from "node:path";
import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { requireRole } from "@/lib/auth";
import { resources } from "@/lib/crud";

const UPLOAD_DIR = path.join(process.cwd(), "uploads");

// Resources whose Prisma models have a `photo` column.
const PHOTO_RESOURCES = new Set(["admins", "faculty", "professors", "students"]);

const ALLOWED: Record<string, string> = {
  "image/png": ".png",
  "image/jpeg": ".jpg",
  "image/webp": ".webp",
  "image/gif": ".gif",
};

const MAX_BYTES = 5 * 1024 * 1024; // 5 MB

/** Upload a photo for a user record and store its filename in the `photo` column. */
export async function POST(request: Request) {
  const guard = await requireRole(["admin", "faculty"]);
  if (guard.response) return guard.response;

  let form: FormData;
  try {
    form = await request.formData();
  } catch {
    return NextResponse.json({ success: false, message: "Expected multipart form data" }, { status: 400 });
  }

  const resource = String(form.get("resource") ?? "");
  const id = String(form.get("id") ?? "");
  const file = form.get("photo");

  if (!PHOTO_RESOURCES.has(resource) || !id) {
    return NextResponse.json({ success: false, message: "Invalid resource or id" }, { status: 400 });
  }
  if (!(file instanceof File) || file.size === 0) {
    return NextResponse.json({ success: false, message: "No photo file provided" }, { status: 400 });
  }
  if (file.size > MAX_BYTES) {
    return NextResponse.json({ success: false, message: "Photo must be 5 MB or smaller" }, { status: 400 });
  }
  const extension = ALLOWED[file.type];
  if (!extension) {
    return NextResponse.json({ success: false, message: "Only PNG, JPEG, WebP and GIF images are allowed" }, { status: 400 });
  }

  const config = resources[resource];
  const delegate = (prisma as unknown as Record<string, { findUnique: (args: object) => Promise<unknown>; update: (args: object) => Promise<unknown> }>)[config.model];
  const existing = await delegate.findUnique({ where: { [config.idField]: Number(id) } });
  if (!existing) {
    return NextResponse.json({ success: false, message: "Record not found" }, { status: 404 });
  }

  const filename = `${randomUUID()}${extension}`;
  await mkdir(UPLOAD_DIR, { recursive: true });
  await writeFile(path.join(UPLOAD_DIR, filename), new Uint8Array(await file.arrayBuffer()));

  try {
    await delegate.update({ where: { [config.idField]: Number(id) }, data: { photo: filename } });
  } catch (error) {
    // Avoid leaving an orphaned file if the DB update fails.
    const { unlink } = await import("node:fs/promises");
    await unlink(path.join(UPLOAD_DIR, filename)).catch(() => {});
    throw error;
  }

  return NextResponse.json({ success: true, photo: filename });
}
