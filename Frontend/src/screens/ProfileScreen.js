import React from 'react';
import { View, StyleSheet, ScrollView, Alert } from 'react-native';
import { Text, Card, Button, List, Avatar, Title } from 'react-native-paper';
import { useAuth } from '../context/AuthContext';
import { theme } from '../theme/theme';

export default function ProfileScreen() {
  const { user, logout } = useAuth();

  const handleLogout = () => {
    Alert.alert(
      'Logout',
      'Are you sure you want to logout?',
      [
        { text: 'Cancel', style: 'cancel' },
        { text: 'Logout', style: 'destructive', onPress: logout },
      ]
    );
  };

  return (
    <ScrollView style={styles.container}>
      <Card style={styles.profileCard}>
        <Card.Content style={styles.profileContent}>
          <Avatar.Text 
            size={80} 
            label={user?.first_name?.charAt(0) || 'U'} 
            style={styles.avatar}
          />
          <Title style={styles.userName}>
            {user?.first_name} {user?.last_name}
          </Title>
          <Text style={styles.userEmail}>{user?.email}</Text>
        </Card.Content>
      </Card>

      <Card style={styles.menuCard}>
        <List.Item
          title="Edit Profile"
          left={props => <List.Icon {...props} icon="account-edit" />}
          right={props => <List.Icon {...props} icon="chevron-right" />}
        />
        <List.Item
          title="My Orders"
          left={props => <List.Icon {...props} icon="receipt" />}
          right={props => <List.Icon {...props} icon="chevron-right" />}
        />
        <List.Item
          title="Settings"
          left={props => <List.Icon {...props} icon="cog" />}
          right={props => <List.Icon {...props} icon="chevron-right" />}
        />
        <List.Item
          title="Help & Support"
          left={props => <List.Icon {...props} icon="help-circle" />}
          right={props => <List.Icon {...props} icon="chevron-right" />}
        />
      </Card>

      <Button
        mode="contained"
        onPress={handleLogout}
        style={styles.logoutButton}
        buttonColor={theme.colors.error}
      >
        Logout
      </Button>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: theme.colors.background },
  profileCard: { margin: theme.spacing.md },
  profileContent: { alignItems: 'center', padding: theme.spacing.lg },
  avatar: { marginBottom: theme.spacing.md },
  userName: { marginBottom: theme.spacing.sm },
  userEmail: { color: theme.colors.placeholder },
  menuCard: { margin: theme.spacing.md },
  logoutButton: { margin: theme.spacing.md },
});
