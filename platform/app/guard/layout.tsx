"use client"

import Link from "next/link"
import { usePathname } from "next/navigation"
import { Home, MapPin, AlertTriangle, User } from "lucide-react"

export default function GuardLayout({
    children,
}: {
    children: React.ReactNode
}) {
    const pathname = usePathname()

    const navItems = [
        { icon: Home, label: "Home", href: "/guard" },
        { icon: MapPin, label: "Check-in", href: "/guard/checkin" },
        { icon: AlertTriangle, label: "Report", href: "/guard/incident" },
        { icon: User, label: "Profile", href: "/guard/profile" },
    ]

    return (
        <div className="min-h-screen bg-slate-50 pb-20">
            <main className="p-4">
                {children}
            </main>

            <nav className="fixed bottom-0 left-0 right-0 bg-white border-t border-slate-200 px-6 py-3 flex justify-between items-center z-50 safe-area-bottom">
                {navItems.map((item) => {
                    const Icon = item.icon
                    const isActive = pathname === item.href

                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={`flex flex-col items-center gap-1 ${isActive ? "text-indigo-600" : "text-slate-400"
                                }`}
                        >
                            <Icon size={24} strokeWidth={isActive ? 2.5 : 2} />
                            <span className="text-[10px] font-medium">{item.label}</span>
                        </Link>
                    )
                })}
            </nav>
        </div>
    )
}
