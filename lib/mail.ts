import nodemailer from "nodemailer";

export function mailTransport() {
  return nodemailer.createTransport({
    host: process.env.MAIL_HOST ?? "smtp.gmail.com",
    port: Number(process.env.MAIL_PORT ?? 587),
    secure: (process.env.MAIL_ENCRYPTION ?? "tls") === "ssl",
    auth: {
      user: process.env.MAIL_USER ?? "",
      pass: process.env.MAIL_PASSWORD ?? "",
    },
  });
}

export function mailFrom() {
  return {
    address: process.env.MAIL_FROM || process.env.MAIL_USER || "",
    name: process.env.MAIL_FROM_NAME || "LabGuard Attendance System",
  };
}
