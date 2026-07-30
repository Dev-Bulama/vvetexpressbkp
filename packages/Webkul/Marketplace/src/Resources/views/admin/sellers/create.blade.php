<x-admin::layouts>
    <x-slot:title>
        Add Vendor - Marketplace
    </x-slot>

    <x-admin::form :action="route('marketplace.admin.sellers.store')">
        <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
            <p class="text-xl font-bold text-gray-800 dark:text-white">
                Add Vendor
            </p>

            <div class="flex items-center gap-x-2.5">
                <a
                    href="{{ route('marketplace.admin.sellers.index') }}"
                    class="transparent-button hover:bg-gray-200 dark:text-white dark:hover:bg-gray-800"
                >
                    Back
                </a>

                <button type="submit" class="primary-button">
                    Create Vendor
                </button>
            </div>
        </div>

        <p class="mt-2 mb-4 text-sm text-gray-500 dark:text-gray-400">
            An account created here is approved immediately - it does not go through the pending-review queue that
            public seller sign-ups do.
        </p>

        <div class="box-shadow mt-3.5 rounded bg-white p-4 dark:bg-gray-900">
            <div class="flex gap-4 max-sm:flex-wrap">
                <x-admin::form.control-group class="w-full">
                    <x-admin::form.control-group.label class="required">
                        Owner Name
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="name"
                        rules="required"
                        :value="old('name')"
                        label="Owner Name"
                    />

                    <x-admin::form.control-group.error control-name="name" />
                </x-admin::form.control-group>

                <x-admin::form.control-group class="w-full">
                    <x-admin::form.control-group.label class="required">
                        Shop Name
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="shop_name"
                        rules="required"
                        :value="old('shop_name')"
                        label="Shop Name"
                    />

                    <x-admin::form.control-group.error control-name="shop_name" />
                </x-admin::form.control-group>
            </div>

            <div class="flex gap-4 max-sm:flex-wrap">
                <x-admin::form.control-group class="w-full">
                    <x-admin::form.control-group.label class="required">
                        Email
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="email"
                        name="email"
                        rules="required|email"
                        :value="old('email')"
                        label="Email"
                        placeholder="email@example.com"
                    />

                    <x-admin::form.control-group.error control-name="email" />
                </x-admin::form.control-group>

                <x-admin::form.control-group class="w-full">
                    <x-admin::form.control-group.label>
                        Phone
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="phone"
                        :value="old('phone')"
                        label="Phone"
                    />

                    <x-admin::form.control-group.error control-name="phone" />
                </x-admin::form.control-group>
            </div>

            <div class="flex gap-4 max-sm:flex-wrap">
                <x-admin::form.control-group class="w-full">
                    <x-admin::form.control-group.label class="required">
                        Password
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="password"
                        name="password"
                        rules="required|min:8"
                        label="Password"
                    />

                    <x-admin::form.control-group.error control-name="password" />
                </x-admin::form.control-group>

                <x-admin::form.control-group class="w-full">
                    <x-admin::form.control-group.label class="required">
                        Confirm Password
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="password"
                        name="password_confirmation"
                        rules="required"
                        label="Confirm Password"
                    />
                </x-admin::form.control-group>
            </div>
        </div>

        <div class="box-shadow mt-3.5 rounded bg-white p-4 dark:bg-gray-900">
            <p class="mb-1 text-base font-semibold text-gray-800 dark:text-white">
                Pickup Location
            </p>

            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                Required - a vendor with no pickup coordinates can never be assigned a delivery quote, so it will
                never appear as a checkout option for customers. Look the address up on
                <a href="https://www.google.com/maps" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline dark:text-blue-400">Google Maps</a>,
                right-click the exact spot, and copy the coordinates shown at the top of the menu.
            </p>

            <div class="flex gap-4 max-sm:flex-wrap">
                <x-admin::form.control-group class="w-full">
                    <x-admin::form.control-group.label>
                        Address
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="address"
                        :value="old('address')"
                        label="Address"
                    />

                    <x-admin::form.control-group.error control-name="address" />
                </x-admin::form.control-group>

                <x-admin::form.control-group class="w-full">
                    <x-admin::form.control-group.label>
                        City
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="city"
                        :value="old('city')"
                        label="City"
                    />

                    <x-admin::form.control-group.error control-name="city" />
                </x-admin::form.control-group>
            </div>

            <div class="flex gap-4 max-sm:flex-wrap">
                <x-admin::form.control-group class="w-full">
                    <x-admin::form.control-group.label class="required">
                        Latitude
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="latitude"
                        rules="required"
                        :value="old('latitude')"
                        label="Latitude"
                        placeholder="e.g. 6.6059"
                    />

                    <x-admin::form.control-group.error control-name="latitude" />
                </x-admin::form.control-group>

                <x-admin::form.control-group class="w-full">
                    <x-admin::form.control-group.label class="required">
                        Longitude
                    </x-admin::form.control-group.label>

                    <x-admin::form.control-group.control
                        type="text"
                        name="longitude"
                        rules="required"
                        :value="old('longitude')"
                        label="Longitude"
                        placeholder="e.g. 3.3491"
                    />

                    <x-admin::form.control-group.error control-name="longitude" />
                </x-admin::form.control-group>
            </div>
        </div>
    </x-admin::form>
</x-admin::layouts>
