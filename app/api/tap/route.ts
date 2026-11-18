import { NextResponse } from "next/server";
import { processTap } from "@/lib/attendance";

export async function POST(request: Request) {
  try {
    const contentType = request.headers.get("content-type") ?? "";
    let rfidTag: string | undefined;

    if (contentType.includes("application/json")) {
      const body = await request.json();
      rfidTag = body.rfid_tag ?? body.rfidTag;
    } else {
      const form = await request.formData();
      const value = form.get("rfid_tag");
      rfidTag = typeof value === "string" ? value : undefined;
    }

    if (!rfidTag || !String(rfidTag).trim()) {
      return NextResponse.json({ success: false, message: "Missing RFID tag" }, { status: 400 });
    }

    const result = await processTap(String(rfidTag));
    return NextResponse.json(result, { status: result.success ? 200 : 200 });
  } catch (error) {
    console.error("Error processing RFID:", error);
    return NextResponse.json({ success: false, message: "Error processing request" }, { status: 500 });
  }
}
