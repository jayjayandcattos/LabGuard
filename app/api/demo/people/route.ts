import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";

/**
 * TEMPORARY — exists only because the physical RFID scanner is unavailable.
 * Lists registered people so the kiosk can simulate a tap by clicking the ID
 * circle. Disabled by default; set DEMO_TAP_ENABLED=true to use. Remove this
 * route and the kiosk picker once the scanner is repaired.
 */
export async function GET() {
  if (process.env.DEMO_TAP_ENABLED !== "true") {
    return NextResponse.json({ error: "Demo mode is disabled" }, { status: 404 });
  }

  try {
    const [students, professors] = await Promise.all([
      prisma.student_tbl.findMany({
        select: { student_id: true, lastname: true, firstname: true, rfid_tag: true },
        orderBy: { lastname: "asc" },
      }),
      prisma.prof_tbl.findMany({
        select: { employee_id: true, lastname: true, firstname: true, rfid_tag: true },
        orderBy: { lastname: "asc" },
      }),
    ]);

    const people = [
      ...students.map((s) => ({
        tag: s.rfid_tag,
        label: `${s.lastname}, ${s.firstname} (student)`,
      })),
      ...professors.map((p) => ({
        tag: p.rfid_tag,
        label: `${p.lastname}, ${p.firstname} (professor)`,
      })),
    ].filter((person) => person.tag);

    return NextResponse.json({ enabled: true, people });
  } catch (error) {
    console.error("Could not load demo people:", error);
    return NextResponse.json({ error: "Could not load people" }, { status: 500 });
  }
}
