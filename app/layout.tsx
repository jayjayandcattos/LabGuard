import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "LabGuard",
  description: "Smart Computer Laboratory Access & RFID Attendance Management System",
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="en">
      <head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
        <link
          href="https://fonts.googleapis.com/css2?family=Bruno+Ace&family=Monomaniac+One&family=Orbitron:wght@400;700&display=swap"
          rel="stylesheet"
        />
      </head>
      <body>{children}</body>
    </html>
  );
}
