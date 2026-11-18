import bcrypt from "bcryptjs";
import { Prisma } from "@prisma/client";
import { prisma } from "./prisma";
import { requireRole } from "./auth";
import type { Role } from "./session";
import { NextResponse } from "next/server";

type Delegate = {
  findMany: (args?: object) => Promise<unknown[]>;
  findUnique: (args: object) => Promise<unknown | null>;
  create: (args: object) => Promise<unknown>;
  update: (args: object) => Promise<unknown>;
  delete: (args: object) => Promise<unknown>;
};

export type ResourceConfig = {
  model: string;
  idField: string;
  fields: string[];
  numberFields?: string[];
  hashPassword?: boolean;
  include?: object;
  writeRoles?: Role[];
};

export const resources: Record<string, ResourceConfig> = {
  admins: {
    model: "admin_tbl", idField: "admin_user_id",
    fields: ["admin_id", "role_id", "lastname", "firstname", "mi", "email", "password", "photo"],
    hashPassword: true,
  },
  faculty: {
    model: "faculty_tbl", idField: "faculty_user_id",
    fields: ["employee_id", "role_id", "lastname", "firstname", "mi", "email", "password", "rfid_tag", "photo"],
    numberFields: ["role_id"], hashPassword: true,
  },
  professors: {
    model: "prof_tbl", idField: "prof_user_id",
    fields: ["employee_id", "role_id", "lastname", "firstname", "mi", "email", "password", "rfid_tag", "photo"],
    numberFields: ["role_id"], hashPassword: true,
  },
  students: {
    model: "student_tbl", idField: "student_user_id",
    fields: ["student_id", "role_id", "lastname", "firstname", "mi", "email", "rfid_tag", "section_id", "photo", "year_level", "student_status"],
    numberFields: ["role_id", "section_id"],
  },
  sections: {
    model: "section_tbl", idField: "section_id",
    fields: ["section_name", "section_level"],
  },
  subjects: {
    model: "subject_tbl", idField: "subject_id",
    fields: ["subject_code", "subject_name", "prof_user_id"],
    numberFields: ["prof_user_id"],
  },
  rooms: {
    model: "room_tbl", idField: "room_id",
    fields: ["room_number", "room_name", "status"],
    numberFields: ["room_number"],
  },
  schedules: {
    model: "schedule_tbl", idField: "schedule_id",
    fields: ["prof_user_id", "subject_id", "section_id", "room_id", "schedule_time", "schedule_end_time", "schedule_day"],
    numberFields: ["prof_user_id", "subject_id", "section_id", "room_id"],
    writeRoles: ["admin", "faculty"],
  },
  attendance: {
    model: "attendance_tbl", idField: "attendance_id",
    fields: ["student_id", "prof_id", "subject_id", "schedule_id", "room_id", "rfid_tag", "time_in", "time_out", "status", "a_status"],
    numberFields: ["student_id", "prof_id", "subject_id", "schedule_id", "room_id"],
  },
  enrollments: {
    model: "student_enrollment_tbl", idField: "enrollment_id",
    fields: ["student_id", "subject_id", "status", "date_enrolled"],
    numberFields: ["student_id", "subject_id"],
  },
  logs: { model: "login_logs", idField: "log_id", fields: [], writeRoles: [] },
  roles: { model: "roles_tbl", idField: "role_id", fields: [], writeRoles: [] },
};

function delegate(config: ResourceConfig): Delegate {
  return (prisma as unknown as Record<string, Delegate>)[config.model];
}

function coerce(config: ResourceConfig, input: Record<string, unknown>) {
  const data: Record<string, unknown> = {};
  for (const field of config.fields) {
    if (!(field in input)) continue;
    let value = input[field];
    if (value === "") value = null;
    if (value !== null && config.numberFields?.includes(field)) value = Number(value);
    if (field === "password" && config.hashPassword && typeof value === "string" && value) {
      value = bcrypt.hashSync(value, 10);
    }
    data[field] = value;
  }
  return data;
}

function idValue(config: ResourceConfig, raw: string) {
  return config.idField === "admin_id" || config.idField === "student_id" ? raw : Number(raw);
}

export async function listResource(resource: string, url: URL) {
  const config = resources[resource];
  if (!config) return NextResponse.json({ error: "Unknown resource" }, { status: 404 });
  const guard = await requireRole(["admin", "faculty", "professor"]);
  if (guard.response) return guard.response;

  const limit = Math.min(Number(url.searchParams.get("limit") ?? 200), 500);
  const where: Record<string, unknown> = {};
  for (const param of ["section_id", "prof_user_id", "schedule_id", "student_id"]) {
    const value = url.searchParams.get(param);
    if (value && config.fields.includes(param)) where[param] = Number(value);
  }
  // Professors may only see their own schedules. Enforce this server-side so it
  // cannot be bypassed by editing the query string on either professor page.
  if (resource === "schedules" && guard.user.role === "professor") {
    where.prof_user_id = Number(guard.user.userId);
  }
  const rows = await delegate(config).findMany({
    where: Object.keys(where).length ? where : undefined,
    take: limit,
    orderBy: { [config.idField]: "desc" },
    ...(config.include ? { include: config.include } : {}),
  });
  return NextResponse.json(rows);
}

export async function createResource(resource: string, request: Request) {
  const config = resources[resource];
  if (!config || config.writeRoles?.length === 0) {
    return NextResponse.json({ error: "Unknown or read-only resource" }, { status: 404 });
  }
  const guard = await requireRole(config.writeRoles ?? ["admin"]);
  if (guard.response) return guard.response;

  let body: Record<string, unknown>;
  try { body = await request.json(); } catch { return NextResponse.json({ error: "Invalid JSON" }, { status: 400 }); }
  const data = coerce(config, body);
  if (Object.keys(data).length === 0) return NextResponse.json({ error: "No valid fields" }, { status: 400 });

  try {
    const created = await delegate(config).create({ data });
    return NextResponse.json(created, { status: 201 });
  } catch (error) {
    if (error instanceof Prisma.PrismaClientKnownRequestError && error.code === "P2002") {
      return NextResponse.json({ error: "A record with that unique value already exists" }, { status: 409 });
    }
    console.error(`create ${resource} failed:`, error);
    return NextResponse.json({ error: "Could not create record" }, { status: 500 });
  }
}

export async function getResource(resource: string, id: string) {
  const config = resources[resource];
  if (!config) return NextResponse.json({ error: "Unknown resource" }, { status: 404 });
  const guard = await requireRole(["admin", "faculty", "professor"]);
  if (guard.response) return guard.response;

  const row = await delegate(config).findUnique({
    where: { [config.idField]: idValue(config, id) },
    ...(config.include ? { include: config.include } : {}),
  });
  if (!row) return NextResponse.json({ error: "Not found" }, { status: 404 });
  return NextResponse.json(row);
}

export async function updateResource(resource: string, id: string, request: Request) {
  const config = resources[resource];
  if (!config || config.writeRoles?.length === 0) {
    return NextResponse.json({ error: "Unknown or read-only resource" }, { status: 404 });
  }
  const guard = await requireRole(config.writeRoles ?? ["admin"]);
  if (guard.response) return guard.response;

  let body: Record<string, unknown>;
  try { body = await request.json(); } catch { return NextResponse.json({ error: "Invalid JSON" }, { status: 400 }); }
  const data = coerce(config, body);
  if (Object.keys(data).length === 0) return NextResponse.json({ error: "No valid fields" }, { status: 400 });

  try {
    const updated = await delegate(config).update({ where: { [config.idField]: idValue(config, id) }, data });
    return NextResponse.json(updated);
  } catch (error) {
    console.error(`update ${resource} failed:`, error);
    return NextResponse.json({ error: "Could not update record" }, { status: 500 });
  }
}

export async function deleteResource(resource: string, id: string) {
  const config = resources[resource];
  if (!config || config.writeRoles?.length === 0) {
    return NextResponse.json({ error: "Unknown or read-only resource" }, { status: 404 });
  }
  const guard = await requireRole(config.writeRoles ?? ["admin"]);
  if (guard.response) return guard.response;

  try {
    await delegate(config).delete({ where: { [config.idField]: idValue(config, id) } });
    return NextResponse.json({ success: true });
  } catch (error) {
    console.error(`delete ${resource} failed:`, error);
    return NextResponse.json({ error: "Could not delete record" }, { status: 500 });
  }
}
