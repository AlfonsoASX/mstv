import { PrismaClient } from '@prisma/client'
import bcrypt from 'bcryptjs'

const prisma = new PrismaClient()

async function main() {
    const password = await bcrypt.hash('admin123', 10)

    const admin = await prisma.user.upsert({
        where: { username: 'admin' },
        update: {},
        create: {
            username: 'admin',
            password,
            name: 'Admin User',
            role: 'ADMIN',
        },
    })

    console.log({ admin })

    // Create a sample site
    const site = await prisma.site.create({
        data: {
            name: 'Main HQ',
            latitude: 19.4326,
            longitude: -99.1332,
            geofenceRadius: 100,
        },
    })

    console.log({ site })
}

main()
    .then(async () => {
        await prisma.$disconnect()
    })
    .catch(async (e) => {
        console.error(e)
        await prisma.$disconnect()
        process.exit(1)
    })
