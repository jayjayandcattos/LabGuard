import bcrypt from "bcryptjs";
import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { createSessionToken, SESSION_COOKIE, SESSION_MAX_AGE, type Role, type SessionUser } from "@/lib/session";

const roleConfig: Record<Role, { table: string; idField: string }> = {
  admin: { table: "admin_tbl", idField: "admin_id" },
  faculty: { table: "faculty_tbl", idField: "employee_id" },
  professor: { table: "prof_tbl", idField: "prof_user_id" },
};

export async function POST(request: Request) {
  let body: { email?: string; password?: string; role?: string };
  try {
    body = await request.json();
  } catch {
    return NextResponse.json({ success: false, message: "Invalid request" }, { status: 400 });
  }

  const email = (body.email ?? "").trim();
  const password = (body.password ?? "").trim();
  const role = body.role as Role;

  if (!email || !password || !roleConfig[role]) {
    return NextResponse.json({ success: false, message: "Please fill in all fields" }, { status: 400 });
  }

  let user: SessionUser | null = null;

  if (role === "admin") {
    const row = await prisma.admin_tbl.findUnique({ where: { email } });
    if (row && (await bcrypt.compare(password, row.password))) {
      user = { userId: row.admin_id, role, name: `${row.firstname} ${row.lastname}` };
    }
  } else if (role === "faculty") {
    const row = await prisma.faculty_tbl.findUnique({ where: { email } });
    if (row && (await bcrypt.compare(password, row.password))) {
      user = { userId: row.employee_id, role, name: `${row.firstname} ${row.lastname}` };
    }
  } else {
    const row = await prisma.prof_tbl.findUnique({ where: { email } });
    if (row && (await bcrypt.compare(password, row.password))) {
      user = { userId: String(row.prof_user_id), role, name: `${row.firstname} ${row.lastname}` };
    }
  }

  if (!user) {
    return NextResponse.json({ success: false, message: "Invalid email or password" }, { status: 401 });
  }

  // Audit log — the legacy table exists but the old login page never wrote to it.
  try {
    const forwarded = request.headers.get("x-forwarded-for");
    const ip = (forwarded?.split(",")[0] ?? request.headers.get("x-real-ip") ?? "127.0.0.1").trim();
    await prisma.login_logs.create({
      data: { user_id: user.userId, user_role: role, user_name: user.name, ip_address: ip.slice(0, 50) },
    });
  } catch (error) {
    console.error("Could not write login log:", error);
  }

  const response = NextResponse.json({ success: true, user, redirect: redirectFor(role) });
  response.cookies.set(SESSION_COOKIE, createSessionToken(user), {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    path: "/",
    maxAge: SESSION_MAX_AGE,
  });
  return response;
}

function redirectFor(role: Role): string {
  if (role === "admin") return "/admin";
  if (role === "faculty") return "/faculty";
  return "/professor";
}
