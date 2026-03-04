import { prisma } from "@/lib/prisma"
import Link from "next/link"
import { Plus, Trash2, MapPin } from "lucide-react"
import { deleteSite } from "@/lib/actions/sites"

export default async function SitesPage() {
    const sites = await prisma.site.findMany({
        orderBy: { createdAt: "desc" },
    })

    return (
        <div>
            <div className="flex justify-between items-center mb-6">
                <div>
                    <h1 className="heading-2 mb-2">Sites</h1>
                    <p className="text-muted">Manage physical locations and geofences.</p>
                </div>
                <Link href="/admin/sites/create" className="btn btn-primary">
                    <Plus className="w-4 h-4 mr-2" />
                    Add Site
                </Link>
            </div>

            <div className="card overflow-hidden p-0">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th className="px-6 py-4 font-semibold text-slate-900">Name</th>
                                <th className="px-6 py-4 font-semibold text-slate-900">Coordinates</th>
                                <th className="px-6 py-4 font-semibold text-slate-900">Radius (m)</th>
                                <th className="px-6 py-4 font-semibold text-slate-900">Created At</th>
                                <th className="px-6 py-4 font-semibold text-slate-900 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {sites.length === 0 ? (
                                <tr>
                                    <td colSpan={5} className="px-6 py-8 text-center text-muted">
                                        No sites found.
                                    </td>
                                </tr>
                            ) : (
                                sites.map((site) => (
                                    <tr key={site.id} className="hover:bg-slate-50/50">
                                        <td className="px-6 py-4 font-medium text-slate-900">
                                            <div className="flex items-center">
                                                <MapPin className="w-4 h-4 text-slate-400 mr-2" />
                                                {site.name}
                                            </div>
                                        </td>
                                        <td className="px-6 py-4 text-slate-600 font-mono text-xs">
                                            {site.latitude.toFixed(6)}, {site.longitude.toFixed(6)}
                                        </td>
                                        <td className="px-6 py-4 text-slate-600">
                                            {site.geofenceRadius}m
                                        </td>
                                        <td className="px-6 py-4 text-slate-600">
                                            {new Date(site.createdAt).toLocaleDateString()}
                                        </td>
                                        <td className="px-6 py-4 text-right">
                                            <form action={async () => {
                                                "use server"
                                                await deleteSite(site.id)
                                            }}>
                                                <button className="text-slate-400 hover:text-red-600 transition-colors">
                                                    <Trash2 className="w-4 h-4" />
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    )
}
