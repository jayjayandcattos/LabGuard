import { NextResponse } from "next/server";
import { getSession, type Role, type SessionUser } from "./session";

/** Guard for route handlers. Returns the session or a 401/403 response. */
export async function requireRole(
  roles: Role[] | Role,
): Promise<{ user: SessionUser; response?: never } | { user?: never; response: NextResponse }> {
  const user = await getSession();
  if (!user) {
    return { response: NextResponse.json({ success: false, message: "Not authenticated" }, { status: 401 }) };
  }
  const allowed = Array.isArray(roles) ? roles : [roles];
  if (!allowed.includes(user.role)) {
    return { response: NextResponse.json({ success: false, message: "Forbidden" }, { status: 403 }) };
  }
  return { user };
}
