"use server"

import { prisma } from "@/lib/prisma"
import { revalidatePath } from "next/cache"
import { redirect } from "next/navigation"
import { z } from "zod"

const SiteSchema = z.object({
    name: z.string().min(2, "Name must be at least 2 characters"),
    latitude: z.coerce.number().min(-90).max(90),
    longitude: z.coerce.number().min(-180).max(180),
    geofenceRadius: z.coerce.number().min(10, "Radius must be at least 10 meters").default(50),
})

export async function createSite(prevState: any, formData: FormData) {
    const validatedFields = SiteSchema.safeParse({
        name: formData.get("name"),
        latitude: formData.get("latitude"),
        longitude: formData.get("longitude"),
        geofenceRadius: formData.get("geofenceRadius"),
    })

    if (!validatedFields.success) {
        return {
            errors: validatedFields.error.flatten().fieldErrors,
            message: "Missing Fields. Failed to Create Site.",
        }
    }

    const { name, latitude, longitude, geofenceRadius } = validatedFields.data

    try {
        await prisma.site.create({
            data: {
                name,
                latitude,
                longitude,
                geofenceRadius,
            },
        })
    } catch (error) {
        return {
            message: "Database Error: Failed to Create Site.",
        }
    }

    revalidatePath("/admin/sites")
    redirect("/admin/sites")
}

export async function deleteSite(id: string) {
    try {
        await prisma.site.delete({
            where: { id },
        })
        revalidatePath("/admin/sites")
        return { message: "Site Deleted" }
    } catch (error) {
        return { message: "Database Error: Failed to Delete Site." }
    }
}
