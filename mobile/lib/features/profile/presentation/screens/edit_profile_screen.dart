import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

import '../../../../core/widgets/app_avatar.dart';
import '../../../auth/presentation/providers/auth_provider.dart';
import '../providers/profile_provider.dart';

class EditProfileScreen extends ConsumerStatefulWidget {
  const EditProfileScreen({super.key});

  @override
  ConsumerState<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends ConsumerState<EditProfileScreen> {
  late final TextEditingController _nameController;
  late final TextEditingController _phoneController;
  late final TextEditingController _addressController;
  late final TextEditingController _emergencyController;

  @override
  void initState() {
    super.initState();
    final member = (ref.read(authControllerProvider) as AuthAuthenticated).member;
    _nameController = TextEditingController(text: member.name);
    _phoneController = TextEditingController(text: member.phone ?? '');
    _addressController = TextEditingController(text: member.address ?? '');
    _emergencyController = TextEditingController(text: member.emergencyContact ?? '');
  }

  @override
  void dispose() {
    _nameController.dispose();
    _phoneController.dispose();
    _addressController.dispose();
    _emergencyController.dispose();
    super.dispose();
  }

  Future<void> _pickPhoto() async {
    final picked = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 80);
    if (picked == null) return;

    final error = await ref.read(profileControllerProvider.notifier).uploadPhoto(picked.path);
    if (!mounted) return;
    if (error != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error)));
    }
  }

  Future<void> _save() async {
    final error = await ref.read(profileControllerProvider.notifier).update(
          name: _nameController.text.trim(),
          phone: _phoneController.text.trim(),
          address: _addressController.text.trim(),
          emergencyContact: _emergencyController.text.trim(),
        );

    if (!mounted) return;
    if (error != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error)));
    } else {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Profile updated')));
      Navigator.of(context).pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    final saving = ref.watch(profileControllerProvider).isLoading;
    final member = (ref.watch(authControllerProvider) as AuthAuthenticated).member;

    return Scaffold(
      appBar: AppBar(title: const Text('Edit Profile')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Center(
            child: Stack(
              children: [
                AppAvatar(imageUrl: member.avatar, name: member.name, radius: 44),
                Positioned(
                  bottom: 0,
                  right: 0,
                  child: GestureDetector(
                    onTap: _pickPhoto,
                    child: CircleAvatar(
                      radius: 15,
                      backgroundColor: Theme.of(context).colorScheme.primary,
                      child: const Icon(Icons.camera_alt_outlined, size: 15, color: Colors.white),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 28),
          TextField(controller: _nameController, decoration: const InputDecoration(labelText: 'Full Name')),
          const SizedBox(height: 14),
          TextField(controller: _phoneController, decoration: const InputDecoration(labelText: 'Phone'), keyboardType: TextInputType.phone),
          const SizedBox(height: 14),
          TextField(controller: _addressController, decoration: const InputDecoration(labelText: 'Address')),
          const SizedBox(height: 14),
          TextField(controller: _emergencyController, decoration: const InputDecoration(labelText: 'Emergency Contact')),
          const SizedBox(height: 28),
          ElevatedButton(
            onPressed: saving ? null : _save,
            child: saving
                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white))
                : const Text('Save Changes'),
          ),
        ],
      ),
    );
  }
}
